<?php

declare(strict_types=1);

namespace App\Tests\Chart;

use App\Chart\TimeRange;
use PHPUnit\Framework\TestCase;

final class TimeRangeTest extends TestCase
{
    public function testPeriodStringsAreUnchanged(): void
    {
        $expected = [
            TimeRange::LAST_5_MIN->value => '15s',
            TimeRange::LAST_15_MIN->value => '15s',
            TimeRange::LAST_30_MIN->value => '30s',
            TimeRange::LAST_1_HOUR->value => '1m',
            TimeRange::LAST_3_HOURS->value => '3m',
            TimeRange::LAST_6_HOURS->value => '6m',
            TimeRange::LAST_12_HOURS->value => '12m',
            TimeRange::LAST_24_HOURS->value => '24m',
            TimeRange::LAST_7_DAYS->value => '168m',
            TimeRange::LAST_48_HOURS->value => '336m',
            TimeRange::LAST_15_DAYS->value => '900m',
            TimeRange::TODAY->value => '24m',
            TimeRange::YESTERDAY->value => '24m',
        ];

        foreach (TimeRange::cases() as $range) {
            $this->assertSame($expected[$range->value], $range->asWindowPeriod(), $range->value);
        }
    }

    public function testWindowSecondsMatchThePeriods(): void
    {
        $this->assertSame(15, TimeRange::LAST_15_MIN->asWindowSeconds());
        $this->assertSame(60, TimeRange::LAST_1_HOUR->asWindowSeconds());
        $this->assertSame(168 * 60, TimeRange::LAST_7_DAYS->asWindowSeconds());
    }
}
