<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Symfony;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonyMessenger->value)]
class SymfonyMessenger implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::SymfonyMessenger;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void {}
}
