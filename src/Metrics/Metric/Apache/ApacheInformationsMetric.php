<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheInformations->value)]
class ApacheInformationsMetric implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::ApacheInformations;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void {}
}
