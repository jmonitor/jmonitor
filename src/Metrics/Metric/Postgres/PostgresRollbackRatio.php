<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresRollbackRatio->value)]
class PostgresRollbackRatio implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresRollbackRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $bag = $this->getPostgresActivityBag();

        $gauge
            ->setValueAvailable($bag?->rollbackRatio !== null)
            ->setValue($bag?->rollbackRatio, 2);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
