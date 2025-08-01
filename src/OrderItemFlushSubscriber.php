<?php

namespace App;
use App\Entity\Order\Adjustment;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use App\Entity\Shipping\Shipment;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

final class OrderItemFlushSubscriber
{
    /** backup używany tylko pomiędzy pre- a postFlush */
    private array $backup = [];
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $candidates = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($candidates as $entity) {
            if ($entity instanceof OrderItemUnit) {
                $em->detach($entity);          // usuwa z identityMap i z harmonogramu SQL
            }
            if ($entity instanceof Adjustment && $entity->getOrderItemUnit() !== null) {
                $em->detach($entity);          // usuwa z identityMap i z harmonogramu SQL
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Restore units to Shipments
        $shipments = $uow->getIdentityMap()[\App\Entity\Shipping\Shipment::class] ?? [];
        foreach ($shipments as $shipment) {
            $oid = spl_object_id($shipment);
            $shipment->removeAllUnits();
            if (!empty($this->backup['order_item'][$oid])) {
                foreach ($this->backup['order_item'][$oid] as $unit) {
                    $shipment->addUnit($unit);
                }
            }
        }

        // Restore units to OrderItems
        $orderItems = $uow->getIdentityMap()[\App\Entity\Order\OrderItem::class] ?? [];
        foreach ($orderItems as $item) {
            $oid = spl_object_id($item);
            if (!empty($this->backup['order_item'][$oid])) {
                $em->detach($item);
                $item->clearUnits();

                foreach ($this->backup['order_item'][$oid] as $unit) {
                    $unitId = $unit->getId();

                    // Always use the managed instance if it exists
                    $identityMap = $uow->getIdentityMap()[\App\Entity\Order\OrderItemUnit::class] ?? [];
                    $managedUnit = null;
                    foreach ($identityMap as $existingUnit) {
                        if ($existingUnit->getId() === $unitId) {
                            $managedUnit = $existingUnit;
                            break;
                        }
                    }

                    if ($managedUnit !== null) {
                        $unit = $managedUnit;
                    } elseif ($uow->getEntityState($unit) !== UnitOfWork::STATE_MANAGED) {
                        $uow->registerManaged($unit, ['id' => $unitId], []);
                    }

                    $item->addUnit($unit);

                    if ($unit->getShipment() !== null) {
                        $shipment = $unit->getShipment();
                        $shipmentId = $shipment->getId();
                        $shipmentMap = $uow->getIdentityMap()[\App\Entity\Shipping\Shipment::class] ?? [];
                        $managedShipment = null;
                        foreach ($shipmentMap as $existingShipment) {
                            if ($existingShipment->getId() === $shipmentId) {
                                $managedShipment = $existingShipment;
                                break;
                            }
                        }
                        if ($managedShipment !== null) {
                            $shipment = $managedShipment;
                        } elseif ($uow->getEntityState($shipment) !== UnitOfWork::STATE_MANAGED) {
                            $uow->registerManaged($shipment, ['id' => $shipmentId], []);
                        }

                        $shipment->addUnit($unit);
                    }
                    $item->setQuantity(count($item->getUnits()));
                }
            }
        }

        $this->backup = [];
    }

    public function preFlush(PreFlushEventArgs $args): void
    {

        $this->backup = [];
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        /** @var Order[] $orders */
        $orders = $uow->getIdentityMap()[Order::class] ?? [];

        //wez z $getScheduledEntityInsertions() i getScheduledEntityUpdates() shipments
        $shipments = array_merge(
            array_filter($uow->getScheduledEntityInsertions(), static fn ($e) => $e instanceof Shipment),
            array_filter($uow->getScheduledEntityUpdates(),    static fn ($e) => $e instanceof Shipment),
        );

        foreach ($shipments as $shipment) {
            $units = $shipment->getUnits();
            if ($units->isEmpty()) {
                continue;
            }

            $unitsArray = $units->toArray();

            /* (1) wycięcie referencji -------------------------------------------- */
            $this->backup['shipment'][spl_object_id($shipment)] = $unitsArray;
            $shipment->removeAllUnits();
            if ($shipment->getUnits() instanceof PersistentCollection) {
                $units->takeSnapshot();        // lub: $units->setDirty(false);
            }

            /* (2) „wykręcenie" Unit-ów z UoW -------------------------------------- */
            foreach ($unitsArray as $unit) {
                // odetnij Adjustment-y
                foreach ($unit->getAdjustments() as $adj) {
                    $adj->setAdjustable(null); // odłącz Adjustment od ShipmentUnit
                    $this->unschedule($uow, $adj);
                }
                $this->unschedule($uow, $unit);
            }
        }

        $shipments = $uow->getIdentityMap()[Shipment::class]     // ↖︎ wszystkie,
            ?? [];

        foreach ($shipments as $shipment) {
            $units = $shipment->getUnits();
            if ($units->isEmpty()) {
                continue;
            }

            $unitsArray = $units->toArray();

            /* (1) wycięcie referencji -------------------------------------------- */
            $this->backup['shipment'][spl_object_id($shipment)] = $unitsArray;
            $shipment->removeAllUnits();
            if ($shipment->getUnits() instanceof PersistentCollection) {
                $units->takeSnapshot();        // lub: $units->setDirty(false);
            }

            /* (2) „wykręcenie" Unit-ów z UoW -------------------------------------- */
            foreach ($unitsArray as $unit) {
                // odetnij Adjustment-y
                foreach ($unit->getAdjustments() as $adj) {
                    $this->unschedule($uow, $adj);
                }
                $this->unschedule($uow, $unit);
            }
        }

        $targets = array_merge(
            array_filter($uow->getScheduledEntityInsertions(), static fn ($e) => $e instanceof OrderItem),
            array_filter($uow->getScheduledEntityUpdates(),    static fn ($e) => $e instanceof OrderItem),
        );

        foreach ($targets as $item) {
            $units = $item->getUnits();
            if ($units->isEmpty()) {
                continue;
            }

            $unitsArray = $units->toArray();


            $item->syncItemsData();
            $this->backup['order_item'][spl_object_id($item)] = $unitsArray;
            $item->clearUnits();

            /* (2) wycięcie referencji -------------------------------------------- */

            /* (3) „wykręcenie" Unit-ów z UoW -------------------------------------- */
            foreach ($unitsArray as $unit) {

                // odetnij Adjustment-y
                foreach ($unit->getAdjustments() as $adj) {
                    $adj->setAdjustable(null); // odłącz Adjustment od OrderItemUnit
                    $this->unschedule($uow, $adj);
                }
                $this->unschedule($uow, $unit);
            }

            /* (4) oznacz OrderItem jako zmieniony */
            $meta = $em->getClassMetadata(OrderItem::class);
//            $uow->recomputeSingleEntityChangeSet($meta, $item);
        }

        foreach ($orders as $order) {
            $orderItems = $order->getItems();
            foreach ($orderItems as $item) {
                $units = $item->getUnits();           // PersistentCollection
                if ($units->isEmpty()) {
                    continue;
                }
                $unitsArray = $units->toArray();

                $item->syncItemsData();
                $this->backup['order_item'][spl_object_id($item)] = $units->toArray();
                $item->clearUnits();

                /* ----------------- 2) odcięcie referencji ------------ */
                $units->clear();

                /* ----------------- 3) odczep MANAGED-ów --------------- */
                foreach ($unitsArray as $unit) {
                    $adjArray = $unit->getAdjustments();
                    foreach ($adjArray as $adj) {
                        if ($uow->isInIdentityMap($adj)) {
                            $em->detach($adj);
                        }

                        $this->backup['adjustments'][spl_object_id($unit)][] = $adj;
                        $unit->removeAllAdjustments();
                    }

                    if ($uow->isInIdentityMap($unit)) {
                        $em->detach($unit);                    // teraz wypadnie z identityMap
                    }
                }
                $shipments = $order->getShipments();

                foreach ($shipments as $shipment) {
                    $shipment->removeAllUnits(); // odetnij ShipmentUnit-y
                }

            }
        }
    }

    /** Usuwa encję z listy insertów/aktualizacji i ustawia stan DETACHED */
    private function unschedule(UnitOfWork $uow, object $entity): void
    {
        $state = $uow->getEntityState($entity, UnitOfWork::STATE_NEW);

        // już odczepiona?
        if ($state === UnitOfWork::STATE_DETACHED) {
            return;
        }

        // NEW → nie można klasycznie „detach", trzeba ręcznie:
        if ($state === UnitOfWork::STATE_NEW) {
            // 1) wywal z entityInsertions
            $ref = new \ReflectionClass($uow);
            $prop = $ref->getProperty('entityInsertions');
            $prop->setAccessible(true);
            $insertions = $prop->getValue($uow);
            unset($insertions[spl_object_id($entity)]);
            $prop->setValue($uow, $insertions);

            // 2) stan na DETACHED
            $refState = $ref->getProperty('entityStates');
            $refState->setAccessible(true);
            $states = $refState->getValue($uow);
            $states[spl_object_id($entity)] = UnitOfWork::STATE_DETACHED;
            $refState->setValue($uow, $states);

            return;
        }

        // MANAGED/PERSISTED → zwykłe detach() wystarczy
        $uow->detach($entity);
    }
}
