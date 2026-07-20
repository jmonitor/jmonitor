<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresConnectionsStates->value)]
class PostgresConnectionsStates implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresConnectionsStates;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setMeasurement('postgres_activity')
            ->setFields([
                'conn_active' => 'Active',
                'conn_idle' => 'Idle',
                'conn_idle_in_transaction' => 'Idle in transaction',
            ]);
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
