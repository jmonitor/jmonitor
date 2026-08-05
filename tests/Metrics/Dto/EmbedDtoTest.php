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

    /** A crafted "embed[m][]=x" query string carries an array where a scalar is expected. */
    public function testFromArrayThrowsOnAnArrayValuedMetric(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => ['x']]);
    }

    public function testFromArrayThrowsOnAnArrayValuedRenderer(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'system.cpu_usage', 're' => ['x']]);
    }

    public function testMetricOptionsRoundTripJsonSerialize(): void
    {
        $dto = new EmbedDto(Metric::CaddyReqPerSec, Renderer::Line, chart: new TimeSeriesEmbedOptions(), metricOptions: ['handler' => 'php']);

        $this->assertEquals($dto, EmbedDto::fromArray($dto->jsonSerialize()));
    }

    /** A query string carries everything as a string; options typed as int must survive it. */
    public function testFromArrayRestoresIntegerMetricOptions(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'redis.db_keys', 'o' => ['db' => '3']]);

        $this->assertSame(['db' => 3], $dto->metricOptions);
    }

    public function testFromArrayKeepsNonIntegerMetricOptionsAsStrings(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'caddy.req_per_sec', 'o' => ['handler' => 'file_server']]);

        $this->assertSame(['handler' => 'file_server'], $dto->metricOptions);
    }

    /**
     * The sidebar link carries the whole config as query parameters, which come back as
     * strings: what the card was rendered with must survive that round trip untouched.
     */
    public function testTheConfigSurvivesAQueryStringRoundTrip(): void
    {
        $dto = new EmbedDto(Metric::RedisDbKeys, Renderer::Line, true, new CardEmbedOptions(true), new TimeSeriesEmbedOptions(TimeRange::LAST_1_HOUR, 2.0), ['db' => 3]);

        parse_str(http_build_query(['embed' => $dto->jsonSerialize()]), $query);

        $this->assertIsArray($query['embed']);
        $this->assertEquals($dto, EmbedDto::fromArray($query['embed']));
    }

    public function testFromArrayThrowsOnAnArrayValuedMetricOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'caddy.req_per_sec', 'o' => ['handler' => ['php']]]);
    }

    public function testFromArrayThrowsOnAnUnusableMetricOptionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'caddy.req_per_sec', 'o' => ['not an option name' => 'php']]);
    }

    public function testFromArrayThrowsOnAnOverlongMetricOptionValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'caddy.req_per_sec', 'o' => ['handler' => str_repeat('a', 65)]]);
    }

    public function testFromArrayThrowsOnTooManyMetricOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmbedDto::fromArray(['m' => 'caddy.req_per_sec', 'o' => array_fill_keys(array_map(static fn(int $i): string => 'o' . $i, range(1, 9)), 'x')]);
    }
}
