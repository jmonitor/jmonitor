<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlTmpTables->value)]
class MysqlTmpTables implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlTmpTables;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValue($this->getMysqlStatusBag()?->createdTmpDiskTablesPercent)
            ->setContext([
                'on_disk' => $this->getMysqlStatusBag()?->createdTmpDiskTables,
                'total' => $this->getMysqlStatusBag()?->createdTmpTables,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
