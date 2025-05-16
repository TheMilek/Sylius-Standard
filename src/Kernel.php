<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\SyliusOrderCheckoutStateMachineCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new \App\DependencyInjection\Compiler\RemoveSkipShippingTransitionCompilerPass());
        $container->addCompilerPass(new \App\DependencyInjection\Compiler\RemoveShippingSkippedStateCompilerPass());
    }
}
