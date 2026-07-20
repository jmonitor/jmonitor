<?php

declare(strict_types=1);

namespace App\Alerting\Config;

class BytesPerSecValueThresholdConfig extends BytesValueThresholdConfig
{
    #[\Override]
    public function getDescription(): ?string
    {
        $formatted = parent::getDescription();

        return $formatted !== null ? "{$formatted}/s" : null;
    }
}
