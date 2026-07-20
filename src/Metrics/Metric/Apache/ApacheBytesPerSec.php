<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheBytesPerSec->value)]
class ApacheBytesPerSec implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::ApacheBytesPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $bag = $this->getApacheBag();

        $currentValue = $bag->realBytesPerSecond !== null ? Bytes::parse($bag->realBytesPerSecond)->perSecond() : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('apache')
            ->setFields(['total_bytes' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::BytePerSec);
    }
}
