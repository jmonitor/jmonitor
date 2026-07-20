<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::FrankenPhpWorkerWorkerRestarts->value)]
class FrankenPhpWorkerWorkerRestarts implements BasicMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpWorkerWorkerRestarts;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $index = $options['worker'];
        $worker = $this->getFrankenPhpBag()->workers[$index] ?? null;

        $dto
            ->setValue($worker?->workerRestarts)
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('worker');
        $optionsResolver->setAllowedTypes('worker', 'int');
    }
}
