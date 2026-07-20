<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\ChartColor;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlServerQueriesByType->value)]
class MysqlServerQueriesByType implements TimeSeriesMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::MysqlServerQueriesByType;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setMeasurement('mysql_status')
            ->setFields([
                'com_select' => 'Select',
                'com_insert' => 'Insert',
                'com_update' => 'Update',
                'com_delete' => 'Delete',
            ])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder, TimeRange $timeRange): void {
                $queryBuilder->derivative(unit: $timeRange->asWindowPeriod(), nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setColor('com_select', ChartColor::BLUE)
            ->setColor('com_insert', ChartColor::GREEN)
            ->setColor('com_update', ChartColor::ORANGE)
            ->setColor('com_delete', ChartColor::RED)
        ;
    }
}
