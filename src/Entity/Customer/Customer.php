<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Customer as BaseCustomer;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerInterface as CustomerPoolsCustomerInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPoolAwareTrait;
use Sylius\Plus\Loyalty\Domain\Model\CustomerInterface as LoyaltyCustomerInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_customer")
 */
class Customer extends BaseCustomer implements CustomerInterface, CustomerPoolsCustomerInterface, LoyaltyCustomerInterface
{
    use CustomerPoolAwareTrait;
    use LoyaltyAwareTrait;
}
