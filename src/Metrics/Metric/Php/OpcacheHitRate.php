<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpOpcacheHitRate->value)]
class OpcacheHitRate implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpOpcacheHitRate;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge->setValue($this->getPhpBag()?->opcache->status->statistics->opcacheHitRate, 1);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getPhpBag()?->opcache->status->statistics->opcacheHitRate;
    }
}
