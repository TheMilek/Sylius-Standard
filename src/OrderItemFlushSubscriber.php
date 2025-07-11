<?php

namespace App;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use App\Entity\Shipping\Shipment;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

final class OrderItemFlushSubscriber
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $candidates = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($candidates as $entity) {
            if ($entity instanceof OrderItemUnit || $entity instanceof Adjustment) {
                $em->detach($entity);          // usuwa z identityMap i z harmonogramu SQL
            }
        }
    }
    public function preFlush(PreFlushEventArgs $args): void
    {
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
//        $uow->computeChangeSets();

//        dump($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions(), $uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());

//        dump('==========================================================');
//        dump(        $entityInsertions = $uow->getScheduledEntityInsertions());die;
//        $uow->computeChangeSets();

//        $entityUpdates = $uow->getScheduledEntityUpdates();
//        $entityInsertions = $uow->getScheduledEntityInsertions();

//        dump($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions(), $uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());
//        dump('==========================================================');

//        $this->doShipments($entityInsertions, $uow);
//        $this->doItems($entityInsertions);
//        $this->doOrderItemUnit($entityInsertions, $uow);
//
//        $this->doShipments($entityUpdates, $uow);
//        $this->doItems($entityUpdates);
//        $this->doOrderItemUnit($entityUpdates, $uow);
//        dump($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions(), $uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());
//        dump('==========================================================');

//        $uow->computeChangeSets();

//        dump($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions(), $uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());

//        dump('---');
//        $uow->computeChangeSets();

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

                $item->syncItemsData();
                $item->clearUnits();

                /* ----------------- 2) odcięcie referencji ------------ */
//            $this->backup[spl_object_id($item)] = $units->toArray();
                $units->clear();

                /* ----------------- 3) odczep MANAGED-ów --------------- */
                foreach ($units as $unit) {
                    foreach ($unit->getAdjustments() as $adj) {
                        if ($uow->isInIdentityMap($adj)) {
                            $em->detach($adj);
                        }

                        $unit->removeAllAdjustments();
                    }

                    if ($uow->isInIdentityMap($unit)) {
                        $em->detach($unit);                    // teraz wypadnie z identityMap
                    }
                }
            }
        }
    }

    /**
     * @param array $entityInsertions
     * @param \Doctrine\ORM\UnitOfWork $uow
     * @return Shipment|mixed
     */
    public function doShipments(array $entityInsertions, \Doctrine\ORM\UnitOfWork $uow): void
    {
        foreach ($entityInsertions as $entity) {
            if (!$entity instanceof Shipment) {
                continue;
            }

            foreach ($entity->getUnits() as $unit) {
                $uow->registerManaged(
                    $unit,
                    ['id' => uuid_create()],
                    []
                );
//                $uow->detach($unit);
            }

            $entity->removeAllUnits();
        }
    }

    /**
     * @param array $entityInsertions
     * @return OrderItem|mixed
     */
    public function doItems(array $entityInsertions): void
    {
        foreach ($entityInsertions as $entity) {
            if (!$entity instanceof OrderItem) {
                continue;
            }
            $entity->syncItemsData();
            $entity->clearUnits();
        }
    }

    /**
     * @param array $entityInsertions
     * @param \Doctrine\ORM\UnitOfWork $uow
     * @return void
     */
    public function doOrderItemUnit(array $entityInsertions, \Doctrine\ORM\UnitOfWork $uow): void
    {
        foreach ($entityInsertions as $entity) {
            if (!$entity instanceof OrderItemUnit) {
                continue;
            }

            foreach ($entity->getAdjustments() as $adjustment) {
                $uow->registerManaged(
                    $adjustment,
                    ['id' => uuid_create()],
                    []
                );
                $uow->detach($adjustment);
            }

            $entity->removeAllAdjustments();
            $uow->registerManaged($entity, ['id' => $entity->getId()], []);
            dump('OrderItemUnit', $entity->getId(), $entity->getAdjustments()->count(), $uow->getEntityState($entity));
            $uow->detach($entity);
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
