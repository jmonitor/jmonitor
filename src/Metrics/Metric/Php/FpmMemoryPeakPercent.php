<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmMemoryPeakPercent->value)]
class FpmMemoryPeakPercent implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpFpmMemoryPeakPercent;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge->setValue($this->getPhpBag()?->fpm->memoryPeakPercent);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getPhpBag()?->fpm->memoryPeakPercent;
    }
}
