<?php

declare(strict_types=1);

namespace App\Alerting\Config;

class SymfonyTransportMessagesConfig extends NumberThresholdConfig
{
    public ?string $transportName {
        get => $this->get('transport_name');
        set {
            $this->parameters['transport_name'] = $value;
        }
    }

    public function getDescription(): ?string
    {
        return $this->transportName . ': ' . $this->threshold;
    }
}
