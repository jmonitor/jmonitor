<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;

/**
 * A `label => request count` split of the traffic window, rendered as horizontal bars.
 */
abstract class AbstractCloudflareBreakdown implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $traffic = $this->block($dto, 'traffic');

        if (!$traffic instanceof TrafficBag) {
            return;
        }

        $counts = $this->counts($traffic->window);

        $dto
            ->setValue($this->keepsOrder() ? $counts : WindowBag::top($counts))
            ->setContext([
                'total' => array_sum($counts),
                'labels' => $this->labels($counts),
                'styles' => $this->styles(),
            ])
        ;
    }

    /**
     * @return array<string, int>
     */
    abstract protected function counts(WindowBag $window): array;

    /**
     * Whether the counts are already in display order; otherwise the five largest are kept and the rest folded.
     */
    protected function keepsOrder(): bool
    {
        return false;
    }

    /**
     * @param array<string, int> $counts
     * @return array<string, string> raw label => display label
     */
    protected function labels(array $counts): array
    {
        return [];
    }

    /**
     * @return array<string, string> raw label => Bootstrap background class of its bar
     */
    protected function styles(): array
    {
        return [];
    }
}
