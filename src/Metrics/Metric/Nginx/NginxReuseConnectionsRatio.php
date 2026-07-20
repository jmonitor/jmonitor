<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxReusedConnectionsRatio->value)]
class NginxReuseConnectionsRatio implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::NginxReusedConnectionsRatio;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getNginxBag()->status->keepAliveRatio !== null)
            ->setValue($this->getNginxBag()->status->keepAliveRatio);
    }
}
