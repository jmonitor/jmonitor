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

#[AsTaggedItem(Metric::PhpBaseConfig->value)]
class PhpBaseConfigMetric implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpBaseConfig;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValue([
                'displayErrorsBadge' => $this->getDisplayErrorBadge($this->getPhpBag()->displayErrors),
                'logErrorsBadge' => $this->getLogErrorBadge($this->getPhpBag()->logErrors),
            ])
        ;
    }

    private function getDisplayErrorBadge(?string $displayError): ?Badge
    {
        if ($displayError === null) {
            return null;
        }

        if (mb_strtolower($displayError) === 'stderr') {
            return null;
        }

        return match ((bool) $displayError) {
            true => new Badge(BadgeStyle::WARNING, '1'),
            false => new Badge(BadgeStyle::SUCCESS, '0'),
        };
    }

    private function getLogErrorBadge(?bool $logErrors): ?Badge
    {
        return match ($logErrors) {
            true => new Badge(BadgeStyle::SUCCESS, '1'),
            false => new Badge(BadgeStyle::WARNING, '0'),
            default => null,
        };
    }
}
