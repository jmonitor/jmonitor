<?php

declare(strict_types=1);

namespace App\Webhook\Stripe;

use Stripe\Event;
use Symfony\Component\RemoteEvent\RemoteEvent;

class StripeRemoteEvent extends RemoteEvent
{
    public private(set) Event $stripeEvent;

    public function __construct(Event $stripeEvent)
    {
        parent::__construct($stripeEvent->type, $stripeEvent->id, $stripeEvent->data->object->toArray());

        $this->stripeEvent = $stripeEvent;
    }
}
