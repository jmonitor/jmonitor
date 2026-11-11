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

    protected function labels(array $counts): array
    {
        return array_map(SecurityLabel::action(...), array_combine(array_keys($counts), array_keys($counts)));
    }

    protected function styles(): array
    {
        return [
            'block' => 'bg-danger',
            'managed_challenge' => 'bg-warning',
            'challenge' => 'bg-warning',
            'js_challenge' => 'bg-warning',
            'skip' => 'bg-secondary',
            'log' => 'bg-secondary',
            'allow' => 'bg-secondary',
        ];
    }

    protected function emptyMessage(): string
    {
        return 'No security events over the window.';
    }
}
