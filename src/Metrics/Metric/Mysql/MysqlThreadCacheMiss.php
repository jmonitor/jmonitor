<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlThreadCacheMiss->value)]
class MysqlThreadCacheMiss implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlThreadCacheMiss;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setCardTitle('Cache miss')
            ->setValue($this->getMysqlStatusBag()?->threadsCacheMissPercent, 1)
            ->setContext([
                'created' => $this->getMysqlStatusBag()?->threadsCreated,
                'total' => $this->getMysqlStatusBag()?->connections,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
