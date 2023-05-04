<?php

declare(strict_types=1);

namespace App\Entity\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Plus\ChannelAdmin\Domain\Model\AdminChannelAwareTrait;
use Sylius\Plus\Entity\LastLoginIpAwareInterface;
use Sylius\Plus\Entity\LastLoginIpAwareTrait;
use Sylius\Plus\Rbac\Domain\Model\AdminUserInterface as RbacAdminUserInterface;
use Sylius\Plus\Rbac\Domain\Model\RoleableTrait;
use Sylius\Plus\Rbac\Domain\Model\ToggleablePermissionCheckerTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_admin_user")
 */
#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements AdminUserInterface, RbacAdminUserInterface, LastLoginIpAwareInterface
{
    use AdminChannelAwareTrait;
    use LastLoginIpAwareTrait;
    use RoleableTrait;
    use ToggleablePermissionCheckerTrait;

    public function __construct()
    {
        parent::__construct();

        $this->rolesResources = new ArrayCollection();
    }
}
