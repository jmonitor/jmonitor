<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheRequestDuration->value)]
class ApacheRequestDuration implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::ApacheRequestDuration;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $bag = $this->getApacheBag();

        $lineDto
            // collectors before payload v2, or Apache versions that do not print "Total Duration"
            ->setValueAvailable($bag?->totalDuration !== null)
            ->setCurrentValue($bag?->realDurationPerRequest, 'ms')
            ->setMeasurement('apache')
            ->setFields(['real_duration_per_request' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Millisecond)
        ;
    }
}
