<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Adjustment as BaseAdjustment;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_adjustment')]
class Adjustment extends BaseAdjustment
{
    public function __construct()
    {
    }

    public function __toString(): string
    {
        return implode('', $this->toArray());
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id ?? uuid_create(),
            'amount' => $this->amount,
            'type' => $this->type,
            'label' => $this->label,
            'order' => $this->order?->getId(),
            'orderItem' => $this->orderItem?->getId(),
            'orderItemUnit' => $this->orderItemUnit?->getId(),
            'shipment' => $this->shipment?->getId(),
        ];
    }
}
