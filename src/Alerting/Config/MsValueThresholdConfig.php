<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Utils\Units\MilliSecond;

class MsValueThresholdConfig extends NumberThresholdConfig
{
    #[\Override]
    public function getDescription(): ?string
    {
        return new MilliSecond($this->threshold)->format(includeHtml: false);
    }
}
