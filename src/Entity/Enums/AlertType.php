<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum AlertType: string
{
    // triggers the alert when a value is below or equal to the threshold
    case MinPercentThreshold = 'min_percent_threshold';
    case MinValueThreshold = 'min_value_threshold';

    // triggers the alert when a value is above or equal to the threshold
    case MaxPercentThreshold = 'max_percent_threshold';
    case MaxValueThreshold = 'max_value_threshold';

    case Custom = 'custom';
    case Version = 'version';

    public function thresholdFormHelp(): ?string
    {
        return match ($this) {
            self::MinPercentThreshold,
            self::MinValueThreshold => 'Alert if value is below or equal this threshold',
            self::MaxPercentThreshold,
            self::MaxValueThreshold => 'Alert if value is above or equal this threshold',
            default => null,
        };
    }
}
