<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpOpcacheMemory->value)]
class OpcacheMemory implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpOpcacheMemory;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge->setValue($this->getPhpBag()?->opcache->status->memory->usedPercent, 0);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getPhpBag()?->opcache->status->memory->usedPercent;
    }
}
