<?php
namespace App;

use App\Entity\Order\Adjustment;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderItemUnit;
use App\Entity\Shipping\Shipment;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

final class OrderItemMetadata
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $meta = $args->getClassMetadata();

        if ($meta->getName() === OrderItemUnit::class) {
            $meta->associationMappings['adjustments']['orphanRemoval'] = false;
            $meta->setAssociationOverride(
                'adjustments',
                [
                    'cascade' => [],
                ]
            );
            $meta->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        }

        if ($meta->getName() === Adjustment::class) {
            $meta->setAssociationOverride(
                'orderItemUnit',
                [
                    'cascade' => [],
                ],
            );
        }


        if ($meta->getName() === Shipment::class) {
            $meta->associationMappings['units']['orphanRemoval'] = false;
            $meta->setAssociationOverride(
                'units',
                [
                    'cascade' => [],
                ],
            );
        }
    }
}
