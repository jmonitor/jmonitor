<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareCacheSettings->value)]
class CloudflareCacheSettings extends CloudflareSettings
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareCacheSettings;
    }
}
