<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Entity\Enums\AlertType;

/**
 * Allows a threshold value to be checked easily.
 */
interface ThresholdConfigInterface extends AlertConfigInterface
{
    public function isSatisfiedBy(int|float $value, AlertType $type): bool;
}
