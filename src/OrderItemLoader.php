<?php
namespace App;

use App\Entity\Order\Adjustment;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class OrderItemLoader
{
    public function postLoad(OrderItem $orderItem, LifecycleEventArgs $args): void
    {
        $rawItems = $orderItem->getUnitsData();
        if ($rawItems === []) {
            return;              // nic do hydratacji
        }

        $em          = $args->getObjectManager();


        /* -------- ② Jednym zapytaniem pobierz encje -------- */

        /* -------- ③ Zbuduj obiekty OrderItem -------- */
        $items = new ArrayCollection();
        $uow  = $em->getUnitOfWork();

        foreach ($rawItems as $row) {
            $item = new OrderItemUnit($orderItem);
            $orderItem->removeUnit($item);
            $item->setId($row['id']);
            //shipment

            foreach ($row['adjustments'] as $adjustment) {
                $a = new Adjustment();
                $a->setId($adjustment['id']);
                $a->setType($adjustment['type']);
                $a->setAmount($adjustment['amount']);
                $a->setLabel($adjustment['label']);

                $item->addAdjustment($a);
            }

            $uow->registerManaged(
                $item,
                ['id' => $item->getId()],       // identyfikator
                []                              // początkowy stan pól
            );

            $uow->registerManaged(
                $item,
                ['id' => $item->getId()],       // identyfikator
                []                              // początkowy stan pól
            );
            $uow->addToIdentityMap($item);      // wpis w $identityMap i $entityIdentifiers

            $items->add($item);
        }


        /* -------- ④ Podmień kolekcję w Order -------- */
        $orderItem->replaceUnits($items);
    }
}
