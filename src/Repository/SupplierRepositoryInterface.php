<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Repository;

use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @template T of ShippingMethodInterface
 *
 * @extends RepositoryInterface<T>
 */
interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name, string $locale): array;
}
