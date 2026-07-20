<?php

declare(strict_types=1);

namespace App\Bridge\InfluxDb;

enum RetentionDuration
{
    case DAY;
    case HOUR;
    case MONTH;
    case YEAR;

    public function asSeconds(int $multiplier = 1): int
    {
        return match ($this) {
            self::DAY => 86400 * $multiplier,
            self::HOUR => 3600 * $multiplier,
            self::MONTH => 2678400 * $multiplier,
            self::YEAR => 31536000 * $multiplier,
        };
    }
}
