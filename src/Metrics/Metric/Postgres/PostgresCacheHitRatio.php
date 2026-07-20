<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresCacheHitRatio->value)]
class PostgresCacheHitRatio implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresCacheHitRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $bag = $this->getPostgresActivityBag();

        $gauge
            ->setValueAvailable($bag?->cacheHitRatio !== null)
            ->setValue($bag?->cacheHitRatio, 2)
            ->setContext([
                'hits' => $bag?->blksHit,
                'reads' => $bag?->blksRead,
            ]);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
