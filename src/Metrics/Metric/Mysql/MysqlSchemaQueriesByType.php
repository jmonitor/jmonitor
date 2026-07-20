<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

// not used yet: the tag is commented out on purpose
// #[AsTaggedItem(Metric::MysqlServerQueriesByType->value)]
class MysqlSchemaQueriesByType implements TimeSeriesMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::MysqlServerQueriesByType;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setMeasurement('mysql_queries_count')
            ->setFields([
                'nb_select' => 'Select',
                'nb_insert' => 'Insert',
                'nb_update' => 'Update',
                'nb_delete' => 'Delete',
            ])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder, TimeRange $timeRange): void {
                $queryBuilder->derivative(unit: $timeRange->asWindowPeriod(), nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
