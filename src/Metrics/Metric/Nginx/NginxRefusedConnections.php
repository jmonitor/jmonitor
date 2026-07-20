<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxRefusedConnections->value)]
class NginxRefusedConnections implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::NginxRefusedConnections;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getNginxBag()->status->refusedRequests !== null)
            ->setValue($this->getNginxBag()->status->refusedRequests)
        ;
    }
}
