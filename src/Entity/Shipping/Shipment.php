<?php

declare(strict_types=1);

namespace App\Entity\Shipping;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Shipment as BaseShipment;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceAwareTrait;
use Sylius\Plus\Inventory\Domain\Model\ShipmentInterface as InventoryShipmentInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sylius_shipment")
 */
class Shipment extends BaseShipment implements ShipmentInterface, InventoryShipmentInterface
{
    use InventorySourceAwareTrait;
}
