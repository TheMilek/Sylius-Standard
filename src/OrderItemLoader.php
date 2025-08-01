<?php
namespace App;

use App\Entity\Order\Adjustment;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webmozart\Assert\Assert;

final class OrderItemLoader
{
    public function __construct(private RepositoryInterface $shipmentRepository)
    {
    }

    public function postLoad(OrderItem $orderItem, LifecycleEventArgs $args): void
    {
        $rawItems = $orderItem->getUnitsData();
        if ($rawItems === []) {
            return;
        }

        $em = $args->getObjectManager();

        /* -------- ② Jednym zapytaniem pobierz encje -------- */
   /* -------- ③ Zbuduj obiekty OrderItem -------- */
        $items = new ArrayCollection();
        $uow  = $em->getUnitOfWork();

        foreach ($rawItems as $row) {
            $identityMap = $uow->getIdentityMap()[OrderItemUnit::class] ?? [];
            $managedUnit = null;
            foreach ($identityMap as $existingUnit) {
                if ($existingUnit->getId() === $row['id']) {
                    $managedUnit = $existingUnit;
                    break;
                }
            }

            if ($managedUnit !== null) {
                $item = $managedUnit;
            } else {
                $item = new OrderItemUnit($orderItem);
                $item->setId($row['id']);
                $uow->registerManaged($item, ['id' => $item->getId()], []);
                $uow->addToIdentityMap($item);
            }

            $orderItem->removeUnit($item);
            $item->setId($row['id']);
            if (isset($row['shipmentId'])) {
                //to do zmiany bo jest nie w baze powinno byc wywolanie prox
                $shipment = $this->shipmentRepository->findOneBy(['id' => $row['shipmentId']]);
                Assert::notNull($shipment);
                $shipment->addUnit($item);
            }

            //shipment

            // Handle adjustments
            // setShipment jesli jest ustawiony
            foreach ($row['adjustments'] as $adjustment) {
                $a = new Adjustment();
                $a->setId($adjustment['id']);
                $a->setType($adjustment['type']);
                $a->setAmount($adjustment['amount']);
                $a->setLabel($adjustment['label']);
                if (isset($adjustment['shipmentId'])) {
                    //to do zmiany bo jest nie w baze powinno byc wywolanie proxy
                    $shipment = $this->shipmentRepository->findOneBy(['id' => $adjustment['shipmentId']]);
                    Assert::notNull($shipment);
                    $a->setShipment($shipment);
                }

                $item->addAdjustment($a);
            }

            $uow->registerManaged(
                $item,
                ['id' => $item->getId()],       // identyfikator
                []                              // początkowy stan pól
            );

            $uow->addToIdentityMap($item);      // wpis w $identityMap i $entityIdentifiers

            $items->add($item);
        }

        // Replace the units collection
        $orderItem->replaceUnits($items);
    }
}
