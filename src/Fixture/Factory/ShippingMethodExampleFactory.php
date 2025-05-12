<?php

namespace App\Fixture\Factory;

use Sylius\Bundle\CoreBundle\Fixture\Factory\ShippingMethodExampleFactory as BaseShippingMethodExampleFactory;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingMethodExampleFactory extends BaseShippingMethodExampleFactory
{
    public function __construct(
        // All parent dependencies
        private FactoryInterface $shippingMethodFactory,
        private RepositoryInterface $zoneRepository,
        private RepositoryInterface $shippingCategoryRepository,
        private RepositoryInterface $localeRepository,
        private ChannelRepositoryInterface $channelRepository,
        private RepositoryInterface $taxCategoryRepository,
        ) {
        parent::__construct(
            $shippingMethodFactory,
            $zoneRepository,
            $shippingCategoryRepository,
            $localeRepository,
            $channelRepository,
            $taxCategoryRepository
        );
    }

    public function create(array $options = []): ShippingMethodInterface
    {
        $shippingMethod = parent::create($options);

        if (!isset($options['deliveryConditions'])) {
            return $shippingMethod;
        }

        // Access locales through the parent's public API (if available)
        // or find another way to get locales
        foreach ($this->getLocalesFromRepository() as $localeCode) {
            $shippingMethod->setCurrentLocale($localeCode);
            $shippingMethod->setFallbackLocale($localeCode);
            $shippingMethod->setDeliveryConditions($options['deliveryConditions']);
        }

        return $shippingMethod;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('deliveryConditions', 'some_default_value')
            ->setAllowedTypes('deliveryConditions', ['null', 'string'])
        ;
    }

    private function getLocalesFromRepository(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();
        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }
}
