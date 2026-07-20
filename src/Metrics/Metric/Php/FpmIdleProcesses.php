<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\Metric\ConsumerValueMetricInterface;
use App\Metrics\Renderer\Dto\ConsumerValueDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmIdleProcesses->value)]
class FpmIdleProcesses implements ConsumerValueMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::PhpFpmIdleProcesses;
    }

    public function getConsumer(): Consumer
    {
        return Consumer::PHP;
    }

    /**
     * @param PhpBag $bag
     */
    public function getValue(MetricBagDto $bag): ?int
    {
        return $bag->fpm->idleProcesses;
    }

    public function configureValueDto(ConsumerValueDto $dto): void
    {
        // No badge by default; plain numeric value
    }
}
