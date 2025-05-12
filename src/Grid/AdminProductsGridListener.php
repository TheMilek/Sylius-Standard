<?php

namespace App\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class AdminProductsGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        // Remove
        $grid->removeField('image');

        // Add
        $variantSelectionMethod = Field::fromNameAndType('variantSelectionMethod', 'string');
        $variantSelectionMethod->setLabel('Variant Selection');
        // ...
        $grid->addField($variantSelectionMethod);
    }
}
