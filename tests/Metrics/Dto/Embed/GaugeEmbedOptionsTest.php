<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Embed;

use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use PHPUnit\Framework\TestCase;

class GaugeEmbedOptionsTest extends TestCase
{
    public function testNullAspectRatioLeavesThePipelineDefaultUntouched(): void
    {
        $options = new GaugeRendererOptions();

        new GaugeEmbedOptions()->applyTo($options);

        $this->assertSame(1.7, $options->chartConfig->aspectRatio);
    }

    public function testSetAspectRatioOverridesThePipelineDefault(): void
    {
        $options = new GaugeRendererOptions();

        new GaugeEmbedOptions(1.2)->applyTo($options);

        $this->assertSame(1.2, $options->chartConfig->aspectRatio);
    }

    // Hiding the help icon is a constant of being an embed, not a user option.
    public function testHelpIsAlwaysHidden(): void
    {
        $options = new GaugeRendererOptions();

        new GaugeEmbedOptions()->applyTo($options);

        $this->assertFalse($options->displayHelp);
    }

    public function testApplyingToAnotherRendererOptionsIsANoOp(): void
    {
        $options = new TimeSeriesRendererOptions();

        new GaugeEmbedOptions(1.2)->applyTo($options);

        $this->assertSame(2.8, $options->chartConfig->aspectRatio);
    }

    /** "1e400" overflows to INF, which json_encode() refuses when the DTO is re-serialised. */
    public function testFromArrayIgnoresANonFiniteAspectRatio(): void
    {
        $this->assertNull(GaugeEmbedOptions::fromArray(['aspectRatio' => '1e400'])->aspectRatio);
    }

    public function testFromArrayIgnoresAnAspectRatioAboveTheMaximum(): void
    {
        $this->assertNull(GaugeEmbedOptions::fromArray(['aspectRatio' => GaugeEmbedOptions::ASPECT_RATIO_MAX + 1])->aspectRatio);
    }
}
