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

    protected function label(string $raw): string
    {
        return SecurityLabel::source($raw);
    }

    protected function emptyMessage(): string
    {
        return SecurityLabel::NO_EVENTS;
    }
}
