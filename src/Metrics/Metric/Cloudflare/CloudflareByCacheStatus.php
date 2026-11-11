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

    protected function labels(array $counts): array
    {
        return array_map(ucfirst(...), array_combine(array_keys($counts), array_keys($counts)));
    }

    protected function styles(): array
    {
        return ['hit' => 'bg-success', 'revalidated' => 'bg-success', 'stale' => 'bg-success', 'updating' => 'bg-success', 'miss' => 'bg-warning', 'expired' => 'bg-warning', 'dynamic' => 'bg-info'];
    }
}
