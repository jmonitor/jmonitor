<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::RedisHitRate->value)]
class RedisHitRate implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisHitRate;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValue($this->getRedisBag()?->stats->hitRate, 1)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getRedisBag()?->stats->hitRate;
    }
}
