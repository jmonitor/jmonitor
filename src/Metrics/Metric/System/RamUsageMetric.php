<?php

declare(strict_types=1);

namespace App\Metrics\Metric\System;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SystemRamUsage->value)]
class RamUsageMetric implements GaugeMetricInterface, TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::SystemRamUsage;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge->setValue($this->getSystemBag()?->ram->usedPercent, 0);
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getSystemBag()?->ram->usedPercent, '%')
            ->setMeasurement('system')
            ->setFields(['ram_used_percent' => 'RAM usage percent'])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setYMax(100)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getSystemBag()?->ram->usedPercent;
    }
}
