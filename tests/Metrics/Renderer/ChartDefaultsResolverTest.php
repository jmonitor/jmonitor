<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Chart\Dto\GaugeChartConfiguration;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\MetricLocator;
use App\Metrics\Renderer;
use App\Metrics\Renderer\ChartDefaultsResolver;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ChartDefaultsResolverTest extends TestCase
{
    public function testTimeSeriesDefaultComesFromTheBareConfigurationWhenTheMetricDoesNotOverrideIt(): void
    {
        $config = $this->resolver()->resolve(Metric::SystemCpuUsage, Renderer::Line);

        $this->assertInstanceOf(TimeSeriesChartConfiguration::class, $config);
        $this->assertSame(2.8, $config->aspectRatio);
    }

    public function testTimeSeriesDefaultHonoursTheMetricOverride(): void
    {
        $config = $this->resolver(overrideAspectRatio: 6.0)->resolve(Metric::MysqlSlowQueriesCount, Renderer::Bar);

        $this->assertInstanceOf(TimeSeriesChartConfiguration::class, $config);
        $this->assertSame(6.0, $config->aspectRatio);
    }

    public function testGaugeDefault(): void
    {
        $config = $this->resolver()->resolve(Metric::PhpOpcacheHitRate, Renderer::Gauge);

        $this->assertInstanceOf(GaugeChartConfiguration::class, $config);
        $this->assertSame(1.7, $config->aspectRatio);
    }

    public function testRenderersWithoutAChartResolveToNull(): void
    {
        $this->assertNull($this->resolver()->resolve(Metric::SystemInformations, Renderer::Basic));
    }

    /**
     * The resolver duplicates the call ChartDefaultsConfigurator makes inside the render pipeline,
     * because that configurator needs a DTO it only uses to read $dto->metric. Pin the two together.
     */
    public function testMatchesWhatTheRenderPipelineProduces(): void
    {
        $metric = Metric::MysqlSlowQueriesCount;
        $locator = $this->locator($metric, overrideAspectRatio: 6.0);

        $fromPipeline = new TimeSeriesChartConfiguration();
        $service = $locator->get($metric);
        $this->assertInstanceOf(TimeSeriesMetricInterface::class, $service);
        $service->configureTimeSerieChart($fromPipeline);

        $fromResolver = new ChartDefaultsResolver($locator)->resolve($metric, Renderer::Bar);

        $this->assertEquals($fromPipeline, $fromResolver);
    }

    private function resolver(?float $overrideAspectRatio = null): ChartDefaultsResolver
    {
        return new ChartDefaultsResolver($this->locator(Metric::MysqlSlowQueriesCount, $overrideAspectRatio));
    }

    private function locator(Metric $metric, ?float $overrideAspectRatio): MetricLocator
    {
        $service = new class ($metric, $overrideAspectRatio) implements TimeSeriesMetricInterface {
            public function __construct(
                private readonly Metric $metric,
                private readonly ?float $aspectRatio,
            ) {}

            public function getMetric(): Metric
            {
                return $this->metric;
            }

            public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void {}

            public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
            {
                if ($this->aspectRatio !== null) {
                    $config->setAspectRatio($this->aspectRatio);
                }
            }
        };

        $container = new class ($metric, $service) implements ContainerInterface {
            public function __construct(
                private readonly Metric $metric,
                private readonly TimeSeriesMetricInterface $service,
            ) {}

            public function get(string $id): TimeSeriesMetricInterface
            {
                return $this->service;
            }

            public function has(string $id): bool
            {
                return $id === $this->metric->value;
            }
        };

        return new MetricLocator($container);
    }
}
