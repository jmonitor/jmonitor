<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\MetricBagDto;

class CloudflareBag extends MetricBagDto
{
    public ?ZoneBag $zone {
        get => $this->zone ??= $this->block('zone', ZoneBag::class);
    }

    public ?TrafficBag $traffic {
        get => $this->traffic ??= $this->block('traffic', TrafficBag::class);
    }

    public ?TodayBag $today {
        get => $this->today ??= $this->block('today', TodayBag::class);
    }

    public ?WebAnalyticsBag $webAnalytics {
        get => $this->webAnalytics ??= $this->block('web_analytics', WebAnalyticsBag::class);
    }

    public ?R2Bag $r2 {
        get => $this->r2 ??= $this->block('r2', R2Bag::class);
    }

    public ?ImagesBag $images {
        get => $this->images ??= $this->block('images', ImagesBag::class);
    }

    /**
     * @var array<string, string> block name => reason
     */
    public array $unavailable {
        get => $this->all('unavailable');
    }

    public function unavailableReason(string $block): ?string
    {
        $reason = $this->unavailable[$block] ?? null;

        return $reason === null ? null : (string) $reason;
    }

    /**
     * The agent sent nothing usable: the collector did not boot, or the zone could not be read for no stated reason.
     */
    public function isCollectorDown(): bool
    {
        return $this->hasSkipped() || ($this->zone === null && $this->unavailableReason('zone') === null);
    }

    /**
     * False when the account uses neither R2 nor Images.
     */
    public function hasStorage(): bool
    {
        return $this->unavailableReason('r2') !== 'not_configured' || $this->unavailableReason('images') !== 'not_configured';
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    private function block(string $key, string $class): ?object
    {
        return is_array($this->get($key)) ? new $class($this->all($key)) : null;
    }
}
