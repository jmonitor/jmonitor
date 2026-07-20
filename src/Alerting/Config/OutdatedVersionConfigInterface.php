<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Bridge\Eol\Dto\Cycle;

interface OutdatedVersionConfigInterface extends AlertConfigInterface
{
    public function isSatisfiedBy(Cycle $cycle): bool;
}
