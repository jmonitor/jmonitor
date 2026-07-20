<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxProcessConfig->value)]
class NginxProcessesConfig implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::NginxProcessConfig;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValue($this->getNginxBag())
        ;
    }
}
