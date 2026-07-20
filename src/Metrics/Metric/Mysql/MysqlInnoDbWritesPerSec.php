<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlInnodbWritesPerSec->value)]
class MysqlInnoDbWritesPerSec implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlInnodbWritesPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getMysqlStatusBag()?->innodbDataWritesPerSec, 'writes/s')
            ->setMeasurement('mysql_status')
            ->setFields(['innodb_data_writes' => ''])
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
