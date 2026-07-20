<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheUptime->value)]
class ApacheUptime implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::ApacheUptime;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getApacheBag()?->uptime !== null)
            ->setValue($this->getApacheBag()->uptime)
        ;
    }
}
