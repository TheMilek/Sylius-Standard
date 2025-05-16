<?php

namespace App\DependencyInjection\Compiler;

use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveSkipShippingTransitionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $graph = OrderCheckoutTransitions::GRAPH;
        $transitionToRemove = OrderCheckoutTransitions::TRANSITION_SKIP_SHIPPING;

        $definition = $container->getDefinition(sprintf('state_machine.%s.definition', $graph));
        $transitions = $definition->getArgument(1);

        foreach ($transitions as $i => $ref) {
            $transitionDef = $container->getDefinition((string) $ref);
            if ($transitionDef->getArgument(0) === $transitionToRemove) {
                unset($transitions[$i]);
                break;
            }
        }

        $definition->replaceArgument(1, array_values($transitions));
    }
}
