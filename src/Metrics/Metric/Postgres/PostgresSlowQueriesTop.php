<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresSlowQueriesTop->value)]
class PostgresSlowQueriesTop implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresSlowQueriesTop;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getPostgresSlowQueriesBag();

        $dto
            ->setCardTitle('Top slowest queries')
            ->setValueAvailable($bag?->slowQueries !== null)
            ->setValue($bag);
    }
}
