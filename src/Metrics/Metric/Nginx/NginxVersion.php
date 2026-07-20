<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Bridge\Eol\EndOfLife;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxVersion->value)]
class NginxVersion implements BasicMetricInterface
{
    use BagAwareTrait;

    private EndOfLife $endOfLife;

    public function __construct(EndOfLife $endOfLife)
    {
        $this->endOfLife = $endOfLife;
    }

    public function getMetric(): Metric
    {
        return Metric::NginxVersion;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getNginxBag()?->nginxVersion !== null)
            ->setValue($this->getNginxBag()->nginxVersion)
            ->setBadge($dto->value ? $this->getBadge($dto->value) : null)
        ;
    }

    private function getBadge(string $value): ?Badge
    {
        $cycle = $this->endOfLife->getCycle($value, 'https://endoflife.date/api/nginx.json');

        if (!$cycle) {
            return null;
        }

        return match (true) {
            $cycle->isActive() => new Badge(BadgeStyle::SUCCESS, 'Active support'),
            $cycle->isSecurityFixOnly() => new Badge(BadgeStyle::WARNING, 'Security fixes only'),
            $cycle->isEol() => new Badge(BadgeStyle::DANGER, 'End of life'),
            default => null,
        };
    }
}
