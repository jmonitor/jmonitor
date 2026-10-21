<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\InfluxChartDataProvider;
use App\Range\RangeContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxRefusedConnections->value)]
class NginxRefusedConnections implements BasicMetricInterface
{
    use BagAwareTrait;

    public function __construct(
        private readonly InfluxChartDataProvider $influxDataProvider,
        private readonly RangeContext $rangeContext,
    ) {}

    public function getMetric(): Metric
    {
        return Metric::NginxRefusedConnections;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $refused = $this->getNginxBag()?->status->refusedRequests;
        $range = $this->rangeContext->getRangeDto()->range;

        $dto
            ->setValueAvailable($refused !== null)
            ->setValue($refused)
            ->setContext([
                'range' => $range,
                'increase' => $refused ? $this->influxDataProvider->getTotalIncrease('nginx', 'refused', $range) : null,
            ])
        ;
    }
}
