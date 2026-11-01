<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareSecurity->value)]
class CloudflareSecurity implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    private const array CHALLENGES = ['managed_challenge', 'challenge', 'js_challenge'];

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
        $actions = $window->securityByAction;
        $mitigated = $window->mitigated();
        $challenged = 0;
        foreach (self::CHALLENGES as $challenge) {
            $challenged += $actions[$challenge] ?? 0;
        }

        $sources = $window->securityBySource;
        arsort($sources);

        $dto
            ->setValue($window)
            ->setContext([
                'mitigated' => $mitigated,
                'blocked' => $actions['block'] ?? 0,
                'challenged' => $challenged,
                'share' => $window->requests === 0 ? null : round($mitigated / $window->requests * 100, 1),
                'topSource' => array_key_first($sources),
            ])
        ;
    }
}
