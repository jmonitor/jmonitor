<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Bridge\Eol\EndOfLife;
use App\Metrics\BagAwareTrait;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheVersion->value)]
class ApacheVersion implements BasicMetricInterface
{
    use BagAwareTrait;

    private EndOfLife $endOfLife;

    public function __construct(EndOfLife $endOfLife)
    {
        $this->endOfLife = $endOfLife;
    }

    public function getMetric(): Metric
    {
        return Metric::ApacheVersion;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getApacheBag()?->apacheVersionNumber !== null)
            ->setValue($this->getApacheBag())
            ->setBadge($dto->valueAvailable ? $this->getBadge($dto->value) : null)
        ;
    }

    private function getBadge(ApacheBag $apacheBag): ?Badge
    {
        $cycle = $this->endOfLife->getCycle($apacheBag->apacheVersionNumber, 'https://endoflife.date/api/apache-http-server.json');

        if (!$cycle) {
            return null;
        }

        return match (true) {
            $cycle->isEol() => new Badge(BadgeStyle::DANGER, 'End of life'),
            $cycle->isActive() => new Badge(BadgeStyle::SUCCESS, 'Active'),
            default => null,
        };
    }
}
