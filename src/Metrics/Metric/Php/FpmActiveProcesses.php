<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmActiveProcesses->value)]
class FpmActiveProcesses implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpFpmActiveProcesses;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge->setValue($this->getPhpBag()->fpm->processesUsagePercent);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getPhpBag()->fpm->processesUsagePercent;
    }
}
