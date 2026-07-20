<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use Zenstruck\Bytes;

class BytesValueThresholdConfig extends NumberThresholdConfig
{
    #[\Override]
    public function getDescription(): ?string
    {
        return Bytes::parse($this->threshold)->asBinary()->format();
    }
}
