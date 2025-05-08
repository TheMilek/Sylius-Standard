<?php

namespace App\Menu;

use Sylius\Bundle\AdminBundle\Event\OrderShowMenuBuilderEvent;
use Sylius\Component\Order\OrderTransitions;

final class AdminOrderShowMenuListener
{
    public function addAdminOrderShowMenuItems(OrderShowMenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $order = $event->getOrder();

        if (null !== $order->getId()) {
            $menu
                ->addChild('ship', [
                    'route' => 'sylius_admin_order_shipment_ship',
                    'routeParameters' => ['id' => $order->getId()]
                ])
                ->setAttribute('type', 'transition')
                ->setLabel('Ship')
                ->setLabelAttribute('icon', 'checkmark')
                ->setLabelAttribute('color', 'green')
            ;
        }
    }
}
