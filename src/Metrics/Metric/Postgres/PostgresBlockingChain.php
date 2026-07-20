<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresBlockingChain->value)]
class PostgresBlockingChain implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresBlockingChain;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTitle('Blocking chain')
            ->setValueAvailable((bool) $this->getPostgresActivityBag())
            ->setValue($this->getPostgresActivityBag());
    }
}
