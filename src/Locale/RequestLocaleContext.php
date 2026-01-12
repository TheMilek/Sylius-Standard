<?php

declare(strict_types=1);

namespace App\Locale;

use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestLocaleContext implements LocaleContextInterface
{
    private const DEFAULT_LOCALE = 'en_US';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getLocaleCode(): string
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            throw new LocaleNotFoundException('No request available');
        }

        $locale = $request->attributes->get('_locale');

        if (null !== $locale) {
            return $locale;
        }

        return self::DEFAULT_LOCALE;
    }
}
