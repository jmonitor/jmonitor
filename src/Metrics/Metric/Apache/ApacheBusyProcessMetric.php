<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheBusyProcess->value)]
class ApacheBusyProcessMetric implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::ApacheBusyProcess;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValue($this->getApacheBag()?->scoreboard->usedPercent, 1)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getApacheBag()?->scoreboard->usedPercent;
    }
}
