<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\OrderItem as BaseOrderItem;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order_item')]
class OrderItem extends BaseOrderItem
{
    #[ORM\Column(name: "units_data", type: "json", options: ['jsonb' => true])]
    private $unitsData = [];

    public function getUnitsData(): array
    {
        return $this->unitsData ?? [];
    }
    public function syncItemsData(): void
    {
        $this->unitsData = [];
        /** @var OrderItem $item */
        foreach ($this->units as $unit) {
            $this->unitsData[] = $unit->toArray();
        }
    }
    public function clearUnits(): void
    {
        $this->units->clear();
    }

    public function replaceUnits(\Doctrine\Common\Collections\ArrayCollection $units): void
    {
        $this->units = $units;
        $this->recalculateUnitsTotal();
    }
}
