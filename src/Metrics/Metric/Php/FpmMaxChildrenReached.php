<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\Metric\ConsumerValueMetricInterface;
use App\Metrics\Renderer\Dto\ConsumerValueDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmMaxChildrenReached->value)]
class FpmMaxChildrenReached implements ConsumerValueMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::PhpFpmMaxChildrenReached;
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
        return $bag->fpm->maxChildrenReached;
    }

    public function configureValueDto(ConsumerValueDto $dto): void
    {
        $value = $dto->value;

        $badge = match (true) {
            $value === 0 => new Badge(BadgeStyle::SUCCESS, 'Never reached'),
            $value > 0 => new Badge(BadgeStyle::DANGER, 'Reached'),
            default => null,
        };

        $dto
            ->setBadge($badge)
        ;
    }
}
