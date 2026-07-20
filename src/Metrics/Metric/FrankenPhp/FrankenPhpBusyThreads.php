<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::FrankenPhpBusyThreads->value)]
class FrankenPhpBusyThreads implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpBusyThreads;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setValueAvailable($this->getFrankenPhpBag()?->busyThreads !== null)
            ->setCurrentValue($this->getFrankenPhpBag()?->busyThreads)
            ->setMeasurement('frankenphp')
            ->setFields(['busy_threads' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
        ;
    }
}
