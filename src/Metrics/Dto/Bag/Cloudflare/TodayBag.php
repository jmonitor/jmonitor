<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class TodayBag extends Bag
{
    public ?string $date {
        get => $this->get('date');
    }

    public int $requests {
        get => $this->getInt('requests', 0) ?? 0;
    }

    public int $cachedRequests {
        get => $this->getInt('cached_requests', 0) ?? 0;
    }

    public int $bytes {
        get => $this->getInt('bytes', 0) ?? 0;
    }

    public int $cachedBytes {
        get => $this->getInt('cached_bytes', 0) ?? 0;
    }

    public int $pageViews {
        get => $this->getInt('page_views', 0) ?? 0;
    }

    public int $uniques {
        get => $this->getInt('uniques', 0) ?? 0;
    }

    public int $threats {
        get => $this->getInt('threats', 0) ?? 0;
    }

    public function cachedRatio(): ?float
    {
        return $this->requests === 0 ? null : $this->cachedRequests / $this->requests;
    }
}
