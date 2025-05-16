<?php

namespace App\Grid;

use App\Entity\Addressing\Address;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\ShowAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;

final class AddressGrid extends AbstractGrid implements ResourceAwareGridInterface
{
    public function __construct()
    {
        // TODO inject services if required
    }

    public static function getName(): string
    {
        return 'app_address';
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            // see https://github.com/Sylius/SyliusGridBundle/blob/master/docs/field_types.md
            ->addField(
                StringField::create('firstName')
                    ->setLabel('FirstName')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('lastName')
                    ->setLabel('LastName')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('phoneNumber')
                    ->setLabel('PhoneNumber')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('street')
                    ->setLabel('Street')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('company')
                    ->setLabel('Company')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('city')
                    ->setLabel('City')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('postcode')
                    ->setLabel('Postcode')
                    ->setSortable(true)
            )
            ->addField(
                DateTimeField::create('createdAt')
                    ->setLabel('CreatedAt')
            )
            ->addField(
                DateTimeField::create('updatedAt')
                    ->setLabel('UpdatedAt')
            )
            ->addField(
                StringField::create('countryCode')
                    ->setLabel('CountryCode')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('provinceCode')
                    ->setLabel('ProvinceCode')
                    ->setSortable(true)
            )
            ->addField(
                StringField::create('provinceName')
                    ->setLabel('ProvinceName')
                    ->setSortable(true)
            )
            ->addActionGroup(
                MainActionGroup::create(
                    CreateAction::create(),
                )
            )
            ->addActionGroup(
                ItemActionGroup::create(
                    // ShowAction::create(),
                    UpdateAction::create(),
                    DeleteAction::create()
                )
            )
            ->addActionGroup(
                BulkActionGroup::create(
                    DeleteAction::create()
                )
            )
        ;
    }

    public function getResourceClass(): string
    {
        return Address::class;
    }
}
