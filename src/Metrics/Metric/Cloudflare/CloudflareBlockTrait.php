<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\BagAwareTrait;
use App\Metrics\Dto\Bag\Cloudflare\ImagesBag;
use App\Metrics\Dto\Bag\Cloudflare\R2Bag;
use App\Metrics\Dto\Bag\Cloudflare\TodayBag;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Dto\Bag\Cloudflare\WebAnalyticsBag;
use App\Metrics\Dto\Bag\Cloudflare\ZoneBag;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;

/**
 * A Cloudflare card reads one block of the payload; when the block is missing the card says why instead of rendering.
 * A time series still draws the history it has, and only gives the reason when it has none.
 */
trait CloudflareBlockTrait
{
    use BagAwareTrait;

    protected function block(AbstractDto $dto, string $block): ZoneBag|TrafficBag|TodayBag|WebAnalyticsBag|R2Bag|ImagesBag|null
    {
        $bag = $this->getCloudflareBag();

        if ($bag === null) {
            if (!$dto instanceof TimeSerieDto) {
                $dto->setValueAvailable(false);
            }

            return null;
        }

        if ($bag->isCollectorDown()) {
            $this->unavailable($dto, UnavailableReason::CollectorDown);

            return null;
        }

        $value = match ($block) {
            'zone' => $bag->zone,
            'traffic' => $bag->traffic,
            'today' => $bag->today,
            'web_analytics' => $bag->webAnalytics,
            'r2' => $bag->r2,
            'images' => $bag->images,
            default => throw new \InvalidArgumentException('Unknown Cloudflare block: ' . $block),
        };

        if ($value === null) {
            $this->unavailable($dto, $bag->unavailableReason($block) === null ? UnavailableReason::Forbidden : UnavailableReason::fromPayload($bag->unavailableReason($block)));
        }

        return $value;
    }

    /**
     * A key inside a block that the token could not read (zone.settings, zone.certificate).
     *
     * @template T
     * @param T $value
     * @return T
     */
    protected function subBlock(AbstractDto $dto, mixed $value, UnavailableReason $reason = UnavailableReason::Forbidden): mixed
    {
        if ($value === null || $value === false) {
            $this->unavailable($dto, $reason);
        }

        return $value;
    }

    private function unavailable(AbstractDto $dto, UnavailableReason $reason): void
    {
        $dto
            ->setEmptyDataReason($reason->message())
            ->setEmptyDataLink($reason->link())
        ;

        if (!$dto instanceof TimeSerieDto) {
            $dto->setValueAvailable(false);
        }
    }
}
