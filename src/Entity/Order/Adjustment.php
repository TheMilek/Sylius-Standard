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
        $this->id = uuid_create();
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
        ];
    }
}
