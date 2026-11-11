<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\SecurityActionKind;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareSecurity->value)]
class CloudflareSecurity implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareSecurity;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $traffic = $this->block($dto, 'traffic');

        if (!$traffic instanceof TrafficBag) {
            return;
        }

        $window = $traffic->window;
        $mitigated = $window->mitigated();

        $dto
            ->setValue($window)
            ->setContext([
                'mitigated' => $mitigated,
                'blocked' => $window->securityEvents(SecurityActionKind::Block),
                'challenged' => $window->securityEvents(SecurityActionKind::Challenge),
                'share' => $window->requests === 0 ? null : round($mitigated / $window->requests * 100, 1),
            ])
        ;
    }
}
