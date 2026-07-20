<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlQueriesPerSecond->value)]
class MysqlQueriesPerSec implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlQueriesPerSecond;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getMysqlStatusBag()->questionsPerSecond, 'q/s')
            ->setMeasurement('mysql_status')
            ->setFields(['questions' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
