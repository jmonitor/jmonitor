<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Model\Influx;

use App\Chart\TimeRange;
use App\Metrics\Model\Influx\Functions\AggregateWindow;
use PHPUnit\Framework\TestCase;

final class AggregateWindowTest extends TestCase
{
    public function testTheRangeWindowIsUsedByDefault(): void
    {
        $this->assertSame('aggregateWindow(every: 15s, fn: mean, createEmpty: true)', (string) AggregateWindow::fromRange(TimeRange::LAST_15_MIN));
    }

    public function testTheMinimumWinsOnShortRanges(): void
    {
        $this->assertStringContainsString('every: 1m,', (string) AggregateWindow::fromRange(TimeRange::LAST_5_MIN, minimumSeconds: 60));
        $this->assertStringContainsString('every: 1m,', (string) AggregateWindow::fromRange(TimeRange::LAST_15_MIN, minimumSeconds: 60));
        $this->assertStringContainsString('every: 1m,', (string) AggregateWindow::fromRange(TimeRange::LAST_30_MIN, minimumSeconds: 60));
        $this->assertStringContainsString('every: 90s,', (string) AggregateWindow::fromRange(TimeRange::LAST_30_MIN, minimumSeconds: 90));
    }

    public function testTheRangeWinsOnLongRanges(): void
    {
        $this->assertStringContainsString('every: 1m,', (string) AggregateWindow::fromRange(TimeRange::LAST_1_HOUR, minimumSeconds: 60));
        $this->assertStringContainsString('every: 3m,', (string) AggregateWindow::fromRange(TimeRange::LAST_3_HOURS, minimumSeconds: 60));
    }
}
