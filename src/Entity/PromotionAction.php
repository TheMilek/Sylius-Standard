<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Sylius\Component\Promotion\Model\PromotionAction as BasePromotionAction;

/**
 * @MappedSuperclass
 */
class PromotionAction extends BasePromotionAction
{
}
