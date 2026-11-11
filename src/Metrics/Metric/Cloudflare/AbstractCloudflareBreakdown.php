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
        $rows = $this->keepsOrder() ? $counts : WindowBag::top($counts);
        $keys = array_map(strval(...), array_keys($rows));

        $dto
            ->setValue($rows)
            ->setContext([
                'total' => array_sum($counts),
                'labels' => array_combine($keys, array_map(fn(string $raw): string => $raw === 'other' ? 'Other' : $this->label($raw), $keys)),
                'styles' => array_combine($keys, array_map($this->barClass(...), $keys)),
                'empty' => $this->emptyMessage(),
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

    protected function label(string $raw): string
    {
        return $raw;
    }

    protected function emptyMessage(): string
    {
        return 'No requests over the window.';
    }

    /**
     * Bootstrap background class of the bar, empty for the default colour.
     */
    protected function barClass(string $raw): string
    {
        return '';
    }
}
