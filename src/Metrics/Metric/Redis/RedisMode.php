<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::RedisMode->value)]
class RedisMode implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisMode;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto->setValue($this->getRedisBag()?->server->mode);
    }
}
