<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlInnodbRamRatio->value)]
class MysqlInnoDbRamRatio implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlInnodbRamRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $ram = $this->getSystemBag()?->ram->total;
        $allocated = $this->getMysqlVariablesBag()?->innodbBufferPoolSize;
        $ratio = $allocated !== null && $ram > 0 ? round($allocated / $ram * 100, 2) : null;

        $gauge
            ->setValue($ratio, 0)
            ->setContext([
                'ram' => $ram,
                'allocated' => $allocated,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }
}
