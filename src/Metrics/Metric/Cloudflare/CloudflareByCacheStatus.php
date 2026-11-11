<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareByCacheStatus->value)]
class CloudflareByCacheStatus extends AbstractCloudflareBreakdown
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareByCacheStatus;
    }

    protected function counts(WindowBag $window): array
    {
        return $window->byCacheStatus;
    }

    protected function label(string $raw): string
    {
        return ucfirst($raw);
    }

    protected function barClass(string $raw): string
    {
        return match ($raw) {
            'hit', 'revalidated', 'stale', 'updating' => 'bg-success',
            'miss', 'expired' => 'bg-warning',
            'dynamic' => 'bg-info',
            default => '',
        };
    }
}
