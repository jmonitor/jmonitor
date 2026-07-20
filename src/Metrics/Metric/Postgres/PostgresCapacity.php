<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresCapacity->value)]
class PostgresCapacity implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresCapacity;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable((bool) $this->getPostgresSettingsBag())
            ->setValue($this->getPostgresSettingsBag());
    }
}
