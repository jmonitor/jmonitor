<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmConfig->value)]
class PhpFpmConfig implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::PhpFpmConfig;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void {}
}
