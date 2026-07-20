<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CaddyCpuUsage->value)]
class CaddyCpuUsage implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyCpuUsage;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getCaddyBag()->processCpuSecondsTotalPercent, '%')
            ->setMeasurement('caddy')
            ->setFields(['process_cpu_seconds_total_percent' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
        ;
    }
}
