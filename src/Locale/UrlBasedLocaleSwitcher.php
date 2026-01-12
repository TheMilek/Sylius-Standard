<?php

declare(strict_types=1);

namespace App\Locale;

use Sylius\Bundle\ShopBundle\Locale\LocaleSwitcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UrlBasedLocaleSwitcher implements LocaleSwitcherInterface
{
    private const DEFAULT_LOCALE = 'en_US';

    private const LOCALE_ROUTE_PREFIXES = [
        'en_US' => '',
        'nl_NL' => 'nl_',
        'pl_PL' => 'pl_',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, string $localeCode): RedirectResponse
    {
        $routeName = $this->getRouteNameForLocale($localeCode);

        return new RedirectResponse(
            $this->urlGenerator->generate($routeName, ['_locale' => $localeCode])
        );
    }

    private function getRouteNameForLocale(string $localeCode): string
    {
        $prefix = self::LOCALE_ROUTE_PREFIXES[$localeCode] ?? '';

        return $prefix . 'sylius_shop_homepage';
    }
}
