<?php
namespace App;

use App\Entity\Order\Adjustment;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use App\Entity\Shipping\Shipment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sylius\Component\Core\Repository\OrderItemRepositoryInterface;
use Sylius\Component\Core\Repository\OrderItemUnitRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShipmentUnit;
use Webmozart\Assert\Assert;

final class ShipmentLoader
{
    public function postLoad(Shipment $shipment, LifecycleEventArgs $args): void
    {
        $order = $shipment->getOrder();

        if ($order === null) {
            return;
        }
        //wrocic
        $items = $order->getItems();

        foreach ($items as $item) {}
    }
}
