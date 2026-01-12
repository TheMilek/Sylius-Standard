<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_LOCALE = 'en_US';

    private const LOCALE_PREFIXES = [
        'nl_NL' => 'nl_NL',
        'pl_PL' => 'pl_PL',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $path = $request->getPathInfo();

        // Check if path starts with a locale prefix
        foreach (self::LOCALE_PREFIXES as $prefix => $locale) {
            if (preg_match('#^/' . $prefix . '(/|$)#', $path)) {
                $request->setLocale($locale);
                $request->attributes->set('_locale', $locale);
                return;
            }
        }

        // Default locale for paths without prefix
        $request->setLocale(self::DEFAULT_LOCALE);
        $request->attributes->set('_locale', self::DEFAULT_LOCALE);
    }
}
