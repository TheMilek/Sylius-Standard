<?php

declare(strict_types=1);

namespace App\Entity\Channel;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Channel as BaseChannel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitAwareTrait;
use Sylius\Plus\BusinessUnits\Domain\Model\ChannelInterface as BusinessUnitsChannelInterface;
use Sylius\Plus\CustomerPools\Domain\Model\ChannelInterface as CustomerPoolsChannelInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPoolAwareTrait;
use Sylius\Plus\Returns\Domain\Model\ChannelInterface as ReturnsChannelInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestsAllowedAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_channel")
 */
class Channel extends BaseChannel implements ChannelInterface, ReturnsChannelInterface, BusinessUnitsChannelInterface, CustomerPoolsChannelInterface
{
    use BusinessUnitAwareTrait;
    use CustomerPoolAwareTrait;
    use ReturnRequestsAllowedAwareTrait;
}
