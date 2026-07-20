<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class DatabaseBag extends Bag
{
    public ?int $keys { get => $this->getInt('keys'); }
    public ?int $expires { get => $this->getInt('expires'); }
    public ?int $avgTtl { get => $this->getInt('avg_ttl'); }
    public ?float $expireRatio {
        get {
            if (null === $this->expires || null === $this->keys) {
                return null;
            }

            return round($this->expires / $this->keys * 100, 2);
        }
    }
}
