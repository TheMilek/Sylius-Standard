<?php

declare(strict_types=1);

namespace App\Entity\Shipping;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ShippingMethod as BaseShippingMethod;
use Sylius\Component\Shipping\Model\ShippingMethodTranslationInterface;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_shipping_method')]
class ShippingMethod extends BaseShippingMethod
{
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $deliveryConditions = null;

    public function getDeliveryConditions(): ?string
    {
        return $this->deliveryConditions;
    }

    public function setDeliveryConditions(?string $deliveryConditions): void
    {
        $this->deliveryConditions = $deliveryConditions;
    }

    protected function createTranslation(): ShippingMethodTranslationInterface
    {
        return new ShippingMethodTranslation();
    }
}
