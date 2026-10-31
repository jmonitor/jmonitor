<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class ImagesBag extends Bag
{
    use MonthlyQuotaTrait;

    public const int FREE_UNIQUE_TRANSFORMATIONS = 5000;

    public int $stored {
        get => $this->getInt('stored', 0) ?? 0;
    }

    public ?int $storedQuota {
        get => $this->getInt('stored_quota');
    }

    public int $uniqueTransformations {
        get => $this->getInt('unique_transformations', 0) ?? 0;
    }

    public int $requestsLast24h {
        get => $this->getInt('requests_last_24h', 0) ?? 0;
    }
}
