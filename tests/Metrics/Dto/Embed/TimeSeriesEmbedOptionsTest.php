<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Embed;

use App\Chart\TimeRange;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use PHPUnit\Framework\TestCase;

class TimeSeriesEmbedOptionsTest extends TestCase
{
    public function testNullValuesLeaveThePipelineDefaultsUntouched(): void
    {
        $options = new TimeSeriesRendererOptions();
        $options->chartConfig->setAspectRatio(6.0)->setRange(TimeRange::LAST_1_HOUR);

        new TimeSeriesEmbedOptions()->applyTo($options);

        $this->assertSame(6.0, $options->chartConfig->aspectRatio);
        $this->assertSame(TimeRange::LAST_1_HOUR, $options->chartConfig->range);
    }

    public function testSetValuesOverrideThePipelineDefaults(): void
    {
        $options = new TimeSeriesRendererOptions();
        $options->chartConfig->setAspectRatio(6.0);

        new TimeSeriesEmbedOptions(TimeRange::LAST_24_HOURS, 3.5)->applyTo($options);

        $this->assertSame(3.5, $options->chartConfig->aspectRatio);
        $this->assertSame(TimeRange::LAST_24_HOURS, $options->chartConfig->range);
    }

    // Reachable when the sidebar submits a style change: doing nothing is the correct behaviour.
    public function testApplyingToAnotherRendererOptionsIsANoOp(): void
    {
        $options = new GaugeRendererOptions();

        new TimeSeriesEmbedOptions(null, 3.5)->applyTo($options);

        $this->assertSame(1.7, $options->chartConfig->aspectRatio);
    }

    public function testFromArrayCastsNumericStrings(): void
    {
        $options = TimeSeriesEmbedOptions::fromArray(['range' => 'last_1_hour', 'aspectRatio' => '1.5']);

        $this->assertSame(TimeRange::LAST_1_HOUR, $options->range);
        $this->assertSame(1.5, $options->aspectRatio);
    }

    public function testFromArrayIgnoresNonNumericAspectRatio(): void
    {
        $this->assertNull(TimeSeriesEmbedOptions::fromArray(['aspectRatio' => 'abc'])->aspectRatio);
    }

    /** "1e400" overflows to INF, which json_encode() refuses when the DTO is re-serialised. */
    public function testFromArrayIgnoresANonFiniteAspectRatio(): void
    {
        $this->assertNull(TimeSeriesEmbedOptions::fromArray(['aspectRatio' => '1e400'])->aspectRatio);
    }

    public function testFromArrayIgnoresAnAspectRatioAboveTheMaximum(): void
    {
        $this->assertNull(TimeSeriesEmbedOptions::fromArray(['aspectRatio' => TimeSeriesEmbedOptions::ASPECT_RATIO_MAX + 1])->aspectRatio);
    }

    public function testToArrayDropsNullValues(): void
    {
        $this->assertSame([], new TimeSeriesEmbedOptions()->toArray());
        $this->assertSame(['aspectRatio' => 2.0], new TimeSeriesEmbedOptions(null, 2.0)->toArray());
    }
}
