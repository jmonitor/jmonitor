<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::FrankenPhpWorkerBusyWorkersRate->value)]
class FrankenPhpWorkerBusyWorkersRate implements GaugeMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpWorkerBusyWorkersRate;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $index = $options['worker'];

        $worker = $this->getFrankenPhpBag()->workers[$index] ?? null;

        $gauge
            ->setValue($worker?->busyWorkerPercent, 2)
            ->setContext([
                'worker' => $worker,
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        $worker = $this->getFrankenPhpBag()->workers[$options['worker']] ?? null;

        return $worker?->busyWorkerPercent;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('worker');
        $optionsResolver->setAllowedTypes('worker', 'int');
    }
}
