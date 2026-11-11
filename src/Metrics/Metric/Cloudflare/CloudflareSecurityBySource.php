<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareSecurityBySource->value)]
class CloudflareSecurityBySource extends AbstractCloudflareBreakdown
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareSecurityBySource;
    }

    protected function counts(WindowBag $window): array
    {
        return $window->securityBySource;
    }

    protected function labels(array $counts): array
    {
        return array_map(SecurityLabel::source(...), array_combine(array_keys($counts), array_keys($counts)));
    }

    protected function emptyMessage(): string
    {
        return 'No security events over the window.';
    }
}
