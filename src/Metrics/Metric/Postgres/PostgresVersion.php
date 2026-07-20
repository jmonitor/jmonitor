<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Bridge\Eol\EndOfLife;
use App\Entity\Enums\Component;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresVersion->value)]
class PostgresVersion implements BasicMetricInterface
{
    use BagAwareTrait;

    public function __construct(
        private readonly EndOfLife $endOfLife,
    ) {}

    public function getMetric(): Metric
    {
        return Metric::PostgresVersion;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getPostgresSettingsBag();
        $version = $bag?->serverVersion;

        $dto
            ->setValueAvailable($version !== null)
            ->setValue($bag)
            ->setBadge($version ? $this->getBadge($version) : null)
        ;
    }

    private function getBadge(string $version): ?Badge
    {
        $cycle = $this->endOfLife->getCycle($version, Component::Postgres->eolUrl(), 1);

        if (!$cycle) {
            return null;
        }

        return match (true) {
            $cycle->isActive() => new Badge(BadgeStyle::SUCCESS, 'Active support'),
            $cycle->isSecurityFixOnly() => new Badge(BadgeStyle::WARNING, 'Support ending'),
            $cycle->isEol() => new Badge(BadgeStyle::DANGER, 'End of life'),
            default => null,
        };
    }
}
