<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxActiveConnections->value)]
class NginxActiveConnections implements GaugeMetricInterface, TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::NginxActiveConnections;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValue($this->getNginxBag()?->status->activeConnectionsPercent, 1)
        ;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getNginxBag()?->status->activeConnections)
            ->setMeasurement('nginx')
            ->setFields(['active' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getNginxBag()?->status->activeConnectionsPercent;
    }
}
