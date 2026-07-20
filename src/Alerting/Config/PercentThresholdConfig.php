<?php

declare(strict_types=1);

namespace App\Alerting\Config;

class PercentThresholdConfig extends NumberThresholdConfig
{
    public function getDescription(): ?string
    {
        return "{$this->threshold}%";
    }
}
