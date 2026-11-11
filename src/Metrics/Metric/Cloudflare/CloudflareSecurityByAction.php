<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\SecurityActionKind;
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
        $mitigations = $window->mitigations();
        $passive = array_diff_key($window->securityByAction, $mitigations);
        arsort($mitigations);
        arsort($passive);

        return $mitigations + $passive;
    }

    protected function keepsOrder(): bool
    {
        return true;
    }

    protected function label(string $raw): string
    {
        return SecurityLabel::action($raw);
    }

    protected function barClass(string $raw): string
    {
        return SecurityActionKind::of($raw)->barClass();
    }

    protected function emptyMessage(): string
    {
        return SecurityLabel::NO_EVENTS;
    }
}
