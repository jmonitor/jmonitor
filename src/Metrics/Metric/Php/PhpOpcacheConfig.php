<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpOpcacheConfig->value)]
class PhpOpcacheConfig implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpOpcacheConfig;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getPhpBag();

        $revalidateFrequencyEnabled = $bag->opcache->config->directives->validateTimestamps;
        $revalidateFrequencyValue = $revalidateFrequencyEnabled ? $bag->opcache->config->directives->revalidateFrequency : false;

        $dto
            ->setContext([
                'preloadBadge' => $this->getPreloadBadge($bag->opcache->config->directives->preload),
                'revalidateFrequencyBadge' => $this->getRevalidateFrequencyBadge($revalidateFrequencyValue),
                'revalidateFrequency' => $revalidateFrequencyValue ? $revalidateFrequencyValue . 's' : null,
            ]);
    }

    private function getPreloadBadge(?string $value): Badge
    {
        if (!$value) {
            return new Badge(BadgeStyle::NEUTRAL, 'Disabled');
        }

        return new Badge(BadgeStyle::SUCCESS, 'Active');
    }

    private function getRevalidateFrequencyBadge(int|false|null $value): ?Badge
    {
        if ($value === false) {
            return new Badge(BadgeStyle::SUCCESS, 'Disabled');
        }

        return null;
    }
}
