<?php

namespace App\DependencyInjection\Compiler;

use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveShippingSkippedStateCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $graph = OrderCheckoutTransitions::GRAPH;
        $stateToRemove = OrderCheckoutStates::STATE_SHIPPING_SKIPPED;

        $definition = $container->getDefinition(sprintf('state_machine.%s.definition', $graph));
        $places = $definition->getArgument(0);
        $transitions = $definition->getArgument(1);

        $placeKey = array_search($stateToRemove, $places, true);
        if ($placeKey !== false) {
            unset($places[$placeKey]);
            $places = array_values($places);
        }

        foreach ($transitions as $i => $ref) {
            $transitionDef = $container->getDefinition((string) $ref);
            $from = (array) $transitionDef->getArgument(1);
            $to = $transitionDef->getArgument(2);

            if (in_array($stateToRemove, $from, true) || $to === $stateToRemove) {
                unset($transitions[$i]);
            }
        }

        $definition->replaceArgument(0, $places);
        $definition->replaceArgument(1, array_values($transitions));
    }
}
