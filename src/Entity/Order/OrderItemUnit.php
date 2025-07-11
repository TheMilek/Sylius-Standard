<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\OrderItemUnit as BaseOrderItemUnit;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order_item_unit')]
class OrderItemUnit extends BaseOrderItemUnit
{
    public function __construct(OrderItem $orderItem)
    {
        parent::__construct($orderItem);
        $this->id = uuid_create();
    }
    public function setId($id): void
    {
        $this->id = $id;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'adjustments' => $this->adjustments->map(fn($adjustment) => $adjustment->toArray())->toArray(),
        ];
    }

    public function removeAllAdjustments(): void
    {
        $this->adjustments->clear();
    }
}
