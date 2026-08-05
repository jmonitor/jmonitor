<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Chart\Dto\ChartConfigurationInterface;
use App\Chart\Dto\GaugeChartConfiguration;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\MetricLocator;
use App\Metrics\Renderer;

/**
 * The chart configuration a metric would be rendered with, computed without collected data.
 * Used to display and normalise defaults in forms; never consulted while rendering.
 */
readonly class ChartDefaultsResolver
{
    public function __construct(
        private MetricLocator $metricLocator,
    ) {}

    public function resolve(Metric $metric, Renderer $renderer): ?ChartConfigurationInterface
    {
        return match ($renderer) {
            Renderer::Line, Renderer::Bar => $this->timeSeries($metric),
            Renderer::Gauge => new GaugeChartConfiguration(),
            default => null,
        };
    }

    private function timeSeries(Metric $metric): TimeSeriesChartConfiguration
    {
        $config = new TimeSeriesChartConfiguration();

        if (!$this->metricLocator->has($metric)) {
            return $config;
        }

        $service = $this->metricLocator->get($metric);

        if ($service instanceof TimeSeriesMetricInterface) {
            $service->configureTimeSerieChart($config);
        }

        return $config;
    }
}
