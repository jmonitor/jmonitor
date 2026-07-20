<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlMaxConnectionsReached->value)]
class MysqlMaxConnectionReached implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlMaxConnectionsReached;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable(
                $this->getMysqlStatusBag()?->maxUsedConnections !== null
                && $this->getMysqlVariablesBag()?->maxConnections !== null
                && $this->getMysqlStatusBag()->threadsConnected !== null,
            )
            ->setValue([
                'maxUsedConnections' => $this->getMysqlStatusBag()->maxUsedConnections,
                'maxConnections' => $this->getMysqlVariablesBag()->maxConnections,
                'threadsConnected' => $this->getMysqlStatusBag()->threadsConnected,
            ])
        ;
    }
}
