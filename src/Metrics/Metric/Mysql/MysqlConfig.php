<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlConfig->value)]
class MysqlConfig implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlConfig;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable((bool) $this->getMysqlVariablesBag())
            ->setCardTemplate('dash/project/metrics/card/custom/card-tabs.html.twig')
            ->setValue($this->getMysqlVariablesBag())
        ;
    }
}
