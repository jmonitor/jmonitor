<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\ChartColor;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

#[AsTaggedItem(Metric::MysqlSlowQueriesCount->value)]
class MysqlSlowQueriesCount implements TimeSeriesMetricInterface, BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlSlowQueriesCount;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setMeasurement('mysql_status')
            ->setField('slow_queries')
            ->setQueryBuilder(function (QueryBuilder $queryBuilder, TimeRange $timeRange): void {
                $queryBuilder->derivative(unit: $timeRange->asWindowPeriod(), nonNegative: true);
            })
        ;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTitle('Total')
            ->setValueAvailable($this->getMysqlStatusBag()->slowQueries !== null)
            ->setValue($this->getMysqlStatusBag()->slowQueries)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setChartType(Chart::TYPE_BAR)
            ->setcolor('slow_queries', ChartColor::PINK)
            ->setYMin(0)
            ->setAspectRatio(6)
        ;
    }
}
