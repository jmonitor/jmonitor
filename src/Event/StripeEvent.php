<?php

declare(strict_types=1);

namespace App\Event;

use App\Bridge\Stripe\StripeEventType;
use Symfony\Contracts\EventDispatcher\Event;

class StripeEvent extends Event
{
    public private(set) \Stripe\Event $stripeEvent;

    public StripeEventType $type {
        get => StripeEventType::from($this->stripeEvent->type);
    }

    public function __construct(\Stripe\Event $stripeEvent)
    {
        $this->stripeEvent = $stripeEvent;
    }
}
