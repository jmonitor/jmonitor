<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Symfony;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\Metric\ConsumerValueMetricInterface;
use App\Metrics\Renderer\Dto\ConsumerValueDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonyEnvironment->value)]
class SymfonyEnvironement implements ConsumerValueMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::SymfonyEnvironment;
    }

    public function getConsumer(): Consumer
    {
        return Consumer::SYMFONY;
    }

    /**
     * @param SymfonyBag $bag
     */
    public function getValue(MetricBagDto $bag): ?string
    {
        return $bag->env;
    }

    public function configureValueDto(ConsumerValueDto $dto): void
    {
        /** @var SymfonyBag $bag */
        $bag = $dto->bag;

        $dto
            ->formatValue(fn(?string $value): ?string => $value ? strtoupper($value) : $value)
            ->setBadge($this->getBadge($bag))
        ;
    }

    private function getBadge(SymfonyBag $bag): ?Badge
    {
        if ($bag->debug === null) {
            return null;
        }

        return match ($bag->debug) {
            true => new Badge(BadgeStyle::WARNING, 'Debug: enabled'),
            false => new Badge(BadgeStyle::SUCCESS, 'Debug: disabled'),
        };
    }
}
