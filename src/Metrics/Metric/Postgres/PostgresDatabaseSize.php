<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresDatabaseSize->value)]
class PostgresDatabaseSize implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresDatabaseSize;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $dbSize = $this->getPostgresDatabaseBag()?->dbSize;
        $currentValue = $dbSize !== null ? Bytes::parse($dbSize) : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('postgres_database')
            ->setField('db_size');
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Byte)
        ;
    }
}
