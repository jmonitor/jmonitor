<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareByStatus->value)]
class CloudflareByStatus extends AbstractCloudflareBreakdown
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareByStatus;
    }

    protected function counts(WindowBag $window): array
    {
        $classes = $window->byStatusClass();

        if ($classes['other'] === 0) {
            unset($classes['other']);
        }

        return $classes;
    }

    protected function keepsOrder(): bool
    {
        return true;
    }

    protected function barClass(string $raw): string
    {
        return match ($raw) {
            '2xx' => 'bg-success',
            '3xx' => 'bg-info',
            '4xx' => 'bg-warning',
            '5xx' => 'bg-danger',
            default => '',
        };
    }
}
