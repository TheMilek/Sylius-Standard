<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SyliusOrderCheckoutStateMachineCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $graph = OrderCheckoutTransitions::GRAPH;
        $skipShippingTransition = OrderCheckoutTransitions::TRANSITION_SKIP_SHIPPING;

        // Get the specific state we want to remove
        $shippingSkippedState = OrderCheckoutStates::STATE_SHIPPING_SKIPPED;

        // The service name follows pattern: state_machine.{graph}.definition
        $definition = $container->getDefinition(sprintf('state_machine.%s.definition', $graph));

        // Argument 0 contains all places/states in the workflow
        $placesReferences = $definition->getArgument(0);
        $transitionReferences = $definition->getArgument(1);

        $this->removeTransitionByName(
            $container,
            $transitionReferences,
            $skipShippingTransition
        );

        // Call helper method to remove the OrderCheckoutStates::STATE_SHIPPING_SKIPPED state
        $this->removeStateByName(
            $container,
            $transitionReferences,
            $placesReferences,
            $shippingSkippedState
        );

        // Update the workflow definition with modified places/states
        $definition->replaceArgument(0, $placesReferences);
        $definition->replaceArgument(1, array_values($transitionReferences));
    }

    protected function removeTransitionByName(
        ContainerBuilder $container,
        array &$transitionReferences,
        string $transitionName
    ): void {
        foreach ($transitionReferences as $i => $transitionReference) {
            $transitionDefinition = $container->getDefinition((string) $transitionReference);

            if ($transitionName === $transitionDefinition->getArgument(0)) {
                unset($transitionReferences[$i]);

                return;
            }
        }

        throw new \LogicException(
            sprintf('Unable to locate transition "%s" in state machine.', $transitionName)
        );
    }

    protected function removeStateByName(
        ContainerBuilder $container,
        array &$transitionReferences,
        array &$placesReferences,
        string $placeName
    ): void {
        // Remove the place from the main places list
        $placeKey = array_search($placeName, $placesReferences, true);
        if ($placeKey !== false) {
            unset($placesReferences[$placeKey]);
            $placesReferences = array_values($placesReferences); // Reindex
        }

        // Remove transitions that reference this place
        $transitionsToRemove = [];

        foreach ($transitionReferences as $i => $transitionReference) {
            $transitionDefinition = $container->getDefinition((string) $transitionReference);
            $transitionConfig = $transitionDefinition->getArguments();

            // Check if transition has this place in its "from" or "to" states
            $fromPlaces = (array) ($transitionConfig[1] ?? []);
            $toPlace = $transitionConfig[2] ?? null;

            if (in_array($placeName, $fromPlaces, true) || $toPlace === $placeName) {
                $transitionsToRemove[] = $i;
            }
        }

        // Remove transitions in reverse order to avoid index issues
        foreach (array_reverse($transitionsToRemove) as $index) {
            unset($transitionReferences[$index]);
        }
    }
}
