<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Model\Influx\CounterIncrease;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Range\RangeContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxRefusedConnections->value)]
class NginxRefusedConnections implements BasicMetricInterface
{
    use BagAwareTrait;

    public function __construct(
        private readonly CounterIncrease $counterIncrease,
        private readonly RangeContext $rangeContext,
    ) {}

    public function getMetric(): Metric
    {
        return Metric::NginxRefusedConnections;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $refused = $this->getNginxBag()?->status->refusedRequests;

        $dto
            ->setValueAvailable($refused !== null)
            ->setValue($refused)
            ->setContext([
                'increase' => $refused ? $this->counterIncrease->get('nginx', 'refused', $this->rangeContext->getRangeDto()->range) : null,
            ])
        ;
    }
}
