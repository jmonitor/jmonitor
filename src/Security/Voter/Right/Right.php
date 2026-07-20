<?php

declare(strict_types=1);

namespace App\Security\Voter\Right;

/**
 * The rights testable with is_granted
 */
enum Right: string
{
    case AUTOREFRESH = 'right.autorefresh';
    case TIME_SERIES_CHART = 'right.time_series_chart';
    case ALERTING = 'right.alerting';

    /**
     * @return array<string>
     */
    public static function stringCases(): array
    {
        return array_column(self::cases(), 'value');
    }
}
