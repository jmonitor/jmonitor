<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::RedisMemoryPeak->value)]
class RedisMemoryPeak implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisMemoryPeak;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getRedisBag()->memory->usedPeak !== null)
            ->setValue([
                'max' => $this->getRedisBag()->memory->maxMemory,
                'current' => $this->getRedisBag()->memory->used,
                'peak' => $this->getRedisBag()->memory->usedPeak,
            ])
        ;
    }
}
