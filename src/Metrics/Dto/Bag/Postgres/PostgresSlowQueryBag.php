<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\Bag;

class PostgresSlowQueryBag extends Bag
{
    public ?string $querySample { get => $this->get('query_sample'); }
    public ?int $execCount { get => $this->getInt('exec_count'); }
    public ?float $totalTimeMs { get => $this->getFloat('total_time_ms'); }
    public ?float $avgTimeMs { get => $this->getFloat('avg_time_ms'); }
    public ?float $maxTimeMs { get => $this->getFloat('max_time_ms'); }
    public ?float $stddevTimeMs { get => $this->getFloat('stddev_time_ms'); }
    public ?int $rows { get => $this->getInt('rows'); }
    public ?int $sharedBlksHit { get => $this->getInt('shared_blks_hit'); }
    public ?int $sharedBlksRead { get => $this->getInt('shared_blks_read'); }

    public ?float $totalTimeS {
        get => $this->totalTimeMs !== null ? round($this->totalTimeMs / 1000, 2) : null;
    }

    public ?float $cacheHitRatio {
        get {
            if ($this->sharedBlksHit === null || $this->sharedBlksRead === null) {
                return null;
            }
            $total = $this->sharedBlksHit + $this->sharedBlksRead;

            return $total > 0 ? round($this->sharedBlksHit / $total * 100, 2) : null;
        }
    }
}
