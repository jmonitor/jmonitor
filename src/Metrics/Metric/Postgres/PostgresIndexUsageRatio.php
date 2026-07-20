<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresIndexUsageRatio->value)]
class PostgresIndexUsageRatio implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresIndexUsageRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $bag = $this->getPostgresDatabaseBag();

        $gauge
            ->setValueAvailable($bag?->indexUsageRatio !== null)
            ->setValue($bag?->indexUsageRatio, 2)
            ->setContext([
                'idx' => $bag?->idxScans,
                'seq' => $bag?->seqScans,
            ]);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
