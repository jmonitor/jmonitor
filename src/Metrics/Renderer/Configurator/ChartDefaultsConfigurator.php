<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Configurator;

use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\MetricLocator;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;

/**
 * Calls the DTO function that configures the chart's default options.
 */
class ChartDefaultsConfigurator implements MetricRendererOptionsConfiguratorInterface
{
    private MetricLocator $metricLocator;

    public function __construct(MetricLocator $metricLocator)
    {
        $this->metricLocator = $metricLocator;
    }

    public function supports(RendererOptionsInterface $options, AbstractDto $dto): bool
    {
        return $options instanceof TimeSeriesRendererOptions;
    }

    public function configure(RendererOptionsInterface $options, AbstractDto $dto): void
    {
        $metric = $this->metricLocator->get($dto->metric);
        assert($metric instanceof TimeSeriesMetricInterface);
        assert($options instanceof TimeSeriesRendererOptions);

        $metric->configureTimeSerieChart($options->chartConfig);
    }
}
