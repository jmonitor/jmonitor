<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresDeadTupleRatio->value)]
class PostgresDeadTupleRatio implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresDeadTupleRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $bag = $this->getPostgresDatabaseBag();

        $gauge
            ->setValueAvailable($bag?->deadTupleRatio !== null)
            ->setValue($bag?->deadTupleRatio, 2)
            ->setContext([
                'dead' => $bag?->deadTuples,
                'live' => $bag?->liveTuples,
            ]);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
