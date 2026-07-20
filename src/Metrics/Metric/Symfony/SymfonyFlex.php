<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Symfony;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonyFlex->value)]
class SymfonyFlex implements BasicMetricInterface
{
    use BagAwareTrait;

    private MetricsBagProvider $bagProvider;

    public function __construct(MetricsBagProvider $bagProvider)
    {
        $this->bagProvider = $bagProvider;
    }

    public function getMetric(): Metric
    {
        return Metric::SymfonyFlex;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getSymfonyBag();

        $dto
            ->setBadge($this->getOutdatedBadge($bag->components->flexRecipes->outdatedRecipes))
        ;
    }

    private function getOutdatedBadge(array $outdatedRecipes): Badge
    {
        if ($outdatedRecipes) {
            return new Badge(BadgeStyle::WARNING, 'Outdated');
        }

        return new Badge(BadgeStyle::SUCCESS, 'Up to date');
    }
}
