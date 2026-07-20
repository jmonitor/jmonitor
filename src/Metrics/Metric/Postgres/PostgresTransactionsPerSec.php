<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresTransactionsPerSec->value)]
class PostgresTransactionsPerSec implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresTransactionsPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getPostgresActivityBag()?->transactionsPerSecond, 'tx/s')
            ->setMeasurement('postgres_activity')
            ->setFields([
                'xact_commit' => 'Commits',
                'xact_rollback' => 'Rollbacks',
            ])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            });
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
