<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::RedisDbAvgTtl->value)]
class RedisAvgTtl implements BasicMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisDbAvgTtl;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $db = $this->getRedisBag()->getDatabase($options['db']);

        $dto
            ->setValue($db?->avgTtl)
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('db');
        $optionsResolver->setAllowedTypes('db', 'int');
    }
}
