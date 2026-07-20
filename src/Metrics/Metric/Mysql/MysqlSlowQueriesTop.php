<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlSlowQueriesTop->value)]
class MysqlSlowQueriesTop implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlSlowQueriesTop;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTitle('Top slowest queries')
            ->setValueAvailable($this->getMysqlSlowQueriesBag()->slowQueries !== null)
            ->setValue($this->getMysqlSlowQueriesBag())
        ;
    }
}
