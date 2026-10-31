<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareSecurityByAction->value)]
class CloudflareSecurityByAction extends AbstractCloudflareBreakdown
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareSecurityByAction;
    }

    protected function counts(WindowBag $window): array
    {
        return $window->securityByAction;
    }

    protected function styles(): array
    {
        return ['block' => 'bg-danger', 'managed_challenge' => 'bg-warning', 'challenge' => 'bg-warning', 'js_challenge' => 'bg-warning'];
    }
}
