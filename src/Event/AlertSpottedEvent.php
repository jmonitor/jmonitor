<?php

namespace App\Event;

use App\Alerting\Dto\SpottedAlert;
use Symfony\Contracts\EventDispatcher\Event;

class AlertSpottedEvent extends Event
{
    public function __construct(
        private readonly SpottedAlert $spottedAlert,
    ) {}

    public function getSpottedAlert(): SpottedAlert
    {
        return $this->spottedAlert;
    }
}
