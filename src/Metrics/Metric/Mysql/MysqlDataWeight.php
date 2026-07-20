<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlDataWeight->value)]
class MysqlDataWeight implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlDataWeight;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $currentValue = $this->getMysqlInfoSchemaBag()?->dataWeight['total_length']
            ? Bytes::parse($this->getMysqlInfoSchemaBag()->dataWeight['total_length'])
            : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('mysql_info_schema')
            ->setFields(['total_length' => ''])
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Byte)
        ;
    }
}
