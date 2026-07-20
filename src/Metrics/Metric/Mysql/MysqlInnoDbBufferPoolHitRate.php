<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlInnodbBufferPoolHitRate->value)]
class MysqlInnoDbBufferPoolHitRate implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlInnodbBufferPoolHitRate;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $statusBag = $this->getMysqlStatusBag();
        $gauge
            ->setValue($statusBag?->innoDbBufferPoolHitRate, 2)
            ->setContext([
                'hits' => $statusBag?->innodbBufferPoolReadsFromCache,
                'total' => $statusBag?->innodbBufferPoolReadRequests,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
