<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Order as BaseOrder;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Plus\Returns\Domain\Model\OrderInterface as ReturnsOrderInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order")
 */
class Order extends BaseOrder implements OrderInterface, ReturnsOrderInterface
{
    use ReturnRequestAwareTrait;
}
