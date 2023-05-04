<?php

declare(strict_types=1);

namespace App\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceStocksAwareTrait;
use Sylius\Plus\Inventory\Domain\Model\ProductVariantInterface as InventoryProductVariantInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sylius_product_variant")
 */
class ProductVariant extends BaseProductVariant implements ProductVariantInterface, InventoryProductVariantInterface
{
    use InventorySourceStocksAwareTrait {
        __construct as private initializeProductVariantTrait;
    }

    public function __construct()
    {
        parent::__construct();

        $this->initializeProductVariantTrait();
    }

    protected function createTranslation(): ProductVariantTranslationInterface
    {
        return new ProductVariantTranslation();
    }
}
