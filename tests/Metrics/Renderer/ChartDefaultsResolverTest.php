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
use App\Metrics\Renderer\Configurator\ChartDefaultsConfigurator;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ChartDefaultsResolverTest extends TestCase
{
    public function testTimeSeriesDefaultComesFromTheBareConfigurationWhenTheMetricDoesNotOverrideIt(): void
    {
        $metric = Metric::SystemCpuUsage;
        $config = $this->resolver(metric: $metric)->resolve($metric, Renderer::Line);

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

    public function testAMetricMissingFromTheLocatorFallsBackToTheBareConfiguration(): void
    {
        // Guards a metric added to the Line/Bar renderers without being registered as a service.
        $resolver = new ChartDefaultsResolver($this->locator(Metric::MysqlSlowQueriesCount, 6.0));

        $config = $resolver->resolve(Metric::SystemCpuUsage, Renderer::Line);

        $this->assertInstanceOf(TimeSeriesChartConfiguration::class, $config);
        $this->assertSame(2.8, $config->aspectRatio);
    }

    /**
     * Verifies the resolver produces the same chart configuration as the render pipeline's ChartDefaultsConfigurator.
     * Both are called with the same metric, so they must always stay in sync when that metric has a registered service.
     */
    public function testMatchesWhatTheRenderPipelineProduces(): void
    {
        $metric = Metric::MysqlSlowQueriesCount;
        $locator = $this->locator($metric, overrideAspectRatio: 6.0);

        $options = new TimeSeriesRendererOptions();
        $dto = new TimeSerieDto($metric);
        $configurator = new ChartDefaultsConfigurator($locator);

        $this->assertTrue($configurator->supports($options, $dto), 'Configurator must support TimeSeriesRendererOptions');
        $configurator->configure($options, $dto);
        $fromPipeline = $options->chartConfig;

        $fromResolver = new ChartDefaultsResolver($locator)->resolve($metric, Renderer::Bar);

        $this->assertEquals($fromPipeline, $fromResolver);
    }

    private function resolver(Metric $metric = Metric::MysqlSlowQueriesCount, ?float $overrideAspectRatio = null): ChartDefaultsResolver
    {
        return new ChartDefaultsResolver($this->locator($metric, $overrideAspectRatio));
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
