<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto;

use App\Chart\TimeRange;
use App\Metrics\Dto\Embed\CardEmbedOptions;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use PHPUnit\Framework\TestCase;

class EmbedDtoTest extends TestCase
{
    public function testFromArrayRoundTripsJsonSerialize(): void
    {
        $dto = new EmbedDto(
            Metric::SystemCpuUsage,
            Renderer::Line,
            true,
            new CardEmbedOptions(true),
            new TimeSeriesEmbedOptions(TimeRange::LAST_1_HOUR, 2.0),
        );

        $this->assertEquals($dto, EmbedDto::fromArray($dto->jsonSerialize()));
    }

    public function testGaugeRoundTripsJsonSerialize(): void
    {
        $dto = new EmbedDto(Metric::PhpOpcacheHitRate, Renderer::Gauge, false, new CardEmbedOptions(), new GaugeEmbedOptions(1.2));

        $this->assertEquals($dto, EmbedDto::fromArray($dto->jsonSerialize()));
    }

    public function testFromArrayWithOnlyMetricUsesTheDefaultRendererOptions(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage']);

        $this->assertSame(Metric::SystemCpuUsage, $dto->metric);
        $this->assertNull($dto->renderer);
        $this->assertNull($dto->getRange());
        $this->assertFalse($dto->autoRefresh);
        $this->assertFalse($dto->card->showProjectName);
        // system.cpu_usage defaults to the gauge renderer.
        $this->assertEquals(new GaugeEmbedOptions(), $dto->chart);
    }

    /** Published embeds stored the range at the root, before it moved into the chart options. */
    public function testFromArrayReadsTheLegacyRootRange(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage', 're' => 'line', 'ra' => 'last_1_hour']);

        $this->assertSame(TimeRange::LAST_1_HOUR, $dto->getRange());
    }

    /** Published embeds stored showProjectName at the root as "pn". */
    public function testFromArrayReadsTheLegacyRootShowProjectName(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage', 'pn' => true]);

        $this->assertTrue($dto->card->showProjectName);
    }

    /** The aspect ratio key never changed, so published embeds need no migration. */
    public function testFromArrayReadsTheStoredAspectRatioKey(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage', 're' => 'line', 'cc' => ['aspectRatio' => '1.5']]);

        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, $dto->chart);
        $this->assertSame(1.5, $dto->chart->aspectRatio);
    }

    public function testGetRangeIsNullForARendererWithoutARange(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage', 're' => 'gauge', 'ra' => 'last_1_hour']);

        $this->assertNull($dto->getRange());
    }

    public function testFromArrayThrowsOnUnknownMetric(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'does.not_exist']);
    }

    public function testFromArrayThrowsOnUnknownRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'system.cpu_usage', 're' => 'line', 'cc' => ['range' => 'nope']]);
    }

    public function testFromArrayThrowsOnMissingMetric(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray([]);
    }
}
