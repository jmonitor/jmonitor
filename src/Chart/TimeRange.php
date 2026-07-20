<?php

declare(strict_types=1);

namespace App\Chart;

/**
 * Describes a time range for charts.
 */
enum TimeRange: string
{
    case LAST_5_MIN = 'last_5_min';
    case LAST_15_MIN = 'last_15_min';
    case LAST_30_MIN = 'last_30_min';
    case LAST_1_HOUR = 'last_1_hour';
    case LAST_3_HOURS = 'last_3_hours';
    case LAST_6_HOURS = 'last_6_hours';
    case LAST_12_HOURS = 'last_12_hours';
    case LAST_24_HOURS = 'last_24_hours';
    case LAST_48_HOURS = 'last_48_hours';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_15_DAYS = 'last_15_days';
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';

    /**
     * Format for InfluxDB
     */
    public function asWindowPeriod(): string
    {
        return match ($this) {
            self::LAST_5_MIN => '15s',
            self::LAST_15_MIN => '15s',
            self::LAST_30_MIN => '30s',
            self::LAST_1_HOUR => '1m',
            self::LAST_3_HOURS => '3m',
            self::LAST_6_HOURS => '6m',
            self::LAST_12_HOURS => '12m',
            self::LAST_24_HOURS => '24m',
            self::LAST_7_DAYS => '168m',
            self::LAST_48_HOURS => '336m',
            self::LAST_15_DAYS => '900m',
            self::TODAY => '24m',
            self::YESTERDAY => '24m',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LAST_5_MIN => 'Last 5 minutes',
            self::LAST_15_MIN => 'Last 15 minutes',
            self::LAST_30_MIN => 'Last 30 minutes',
            self::LAST_1_HOUR => 'Last 1 hour',
            self::LAST_3_HOURS => 'Last 3 hours',
            self::LAST_6_HOURS => 'Last 6 hours',
            self::LAST_12_HOURS => 'Last 12 hours',
            self::LAST_24_HOURS => 'Last 24 hours',
            self::LAST_7_DAYS => 'Last 7 days',
            self::LAST_48_HOURS => 'Last 48 hours',
            self::LAST_15_DAYS => 'Last 15 days',
            self::TODAY => 'Today',
            self::YESTERDAY => 'Yesterday',
        };
    }

    public function asStartDateTime(): \DateTimeImmutable
    {
        return match ($this) {
            self::LAST_5_MIN => new \DateTimeImmutable('-5 minutes'),
            self::LAST_15_MIN => new \DateTimeImmutable('-15 minutes'),
            self::LAST_30_MIN => new \DateTimeImmutable('-30 minutes'),
            self::LAST_1_HOUR => new \DateTimeImmutable('-1 hour'),
            self::LAST_3_HOURS => new \DateTimeImmutable('-3 hours'),
            self::LAST_6_HOURS => new \DateTimeImmutable('-6 hours'),
            self::LAST_12_HOURS => new \DateTimeImmutable('-12 hours'),
            self::LAST_24_HOURS => new \DateTimeImmutable('-24 hours'),
            self::LAST_48_HOURS => new \DateTimeImmutable('-48 hours'),
            self::LAST_7_DAYS => new \DateTimeImmutable('-7 days'),
            self::LAST_15_DAYS => new \DateTimeImmutable('-15 days'),
            self::TODAY => new \DateTimeImmutable('today midnight'),
            self::YESTERDAY => new \DateTimeImmutable('yesterday midnight'),
        };
    }

    public function asEndDateTime(): \DateTimeImmutable
    {
        return match ($this) {
            self::TODAY => new \DateTimeImmutable('tomorrow midnight'),
            self::YESTERDAY => new \DateTimeImmutable('today midnight'),
            default => new \DateTimeImmutable('now'),
        };
    }
}
