<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::FrankenPhpBusyThreadsPercent->value)]
class FrankenPhpBusyThreadsPercent implements GaugeMetricInterface, TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpBusyThreadsPercent;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setCardTitle('Busy threads')
            ->setValue($this->getFrankenPhpBag()?->busyThreadsPercent, 1)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getFrankenPhpBag()?->busyThreadsPercent;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setValueAvailable($this->getFrankenPhpBag()?->busyThreadsPercent !== null)
            ->setCurrentValue($this->getFrankenPhpBag()?->busyThreadsPercent, '%')
            ->setMeasurement('frankenphp')
            ->setFields(['busy_threads_percent' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setYMax(100)
        ;
    }
}
