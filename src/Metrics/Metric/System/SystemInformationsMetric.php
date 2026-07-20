<?php

namespace App\Metrics\Metric\System;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SystemInformations->value)]
class SystemInformationsMetric implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::SystemInformations;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void {}
}
