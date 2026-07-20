<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlInnodbBufferPoolUsage->value)]
class MysqlInnoDbBufferPoolUsage implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlInnodbBufferPoolUsage;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $statusBag = $this->getMysqlStatusBag();
        $variablesBag = $this->getMysqlVariablesBag();
        $gauge
            ->setValue($statusBag?->getInnoDbBufferPoolUsage($variablesBag->innodbBufferPoolSize), 0)
            ->setContext([
                'innodbBufferPoolBytesFree' => $statusBag?->innodbBufferPoolBytesFree,
                'innoDbBufferPoolSize' => $variablesBag?->innodbBufferPoolSize,
                'innoDbPagesUsedBytes' => $statusBag?->innoDbPagesUsedBytes,
                'innoDbPagesTotalBytes' => $statusBag?->innoDbPagesTotalBytes,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
