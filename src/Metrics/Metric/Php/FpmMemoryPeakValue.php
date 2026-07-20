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
use Zenstruck\Bytes;

#[AsTaggedItem(Metric::PhpFpmMemoryPeakValue->value)]
class FpmMemoryPeakValue implements ConsumerValueMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::PhpFpmMemoryPeakValue;
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
        return $bag->fpm->memoryPeak;
    }

    public function configureValueDto(ConsumerValueDto $dto): void
    {
        $dto->formatValue(function (?int $value): ?string {
            if ($value === null) {
                return null;
            }

            return (string) new Bytes($value)->asBinary();
        });
    }
}
