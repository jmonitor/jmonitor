<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::RedisDbExpiringKeys->value)]
class RedisDbExpiringKeys implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisDbExpiringKeys;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $db = $this->getRedisBag()->getDatabase($options['db']);

        $dto
            ->setValueAvailable($db !== null)
            ->setValue($db)
        ;
    }
}
