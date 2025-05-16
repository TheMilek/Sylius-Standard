<?php

namespace App\EventListener\Workflow\OrderCheckout;

use Symfony\Component\Workflow\Event\CompletedEvent;

final class SendEmailWithGiftCodeAfterOrderCompletionListener
{
    public function __invoke(CompletedEvent $event): void
    {
        // here you can send an email with a gift
    }
}
