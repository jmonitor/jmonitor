<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class StatisticsBag extends Bag
{
    public ?int $numCachedScripts {
        get => $this->get('num_cached_scripts');
    }

    public ?int $numCachedKeys {
        get => $this->get('num_cached_keys');
    }

    public ?int $maxCachedKeys {
        get => $this->get('max_cached_keys');
    }

    public ?int $hits {
        get => $this->get('hits');
    }

    public ?int $startTime {
        get => $this->get('start_time');
    }

    public ?int $lastRestartTime {
        get => $this->get('last_restart_time');
    }

    public ?int $oomRestarts {
        get => $this->get('oom_restarts');
    }

    public ?int $hashRestarts {
        get => $this->get('hash_restarts');
    }

    public ?int $manualRestarts {
        get => $this->get('manual_restarts');
    }

    public ?int $misses {
        get => $this->get('misses');
    }

    public ?int $blacklistMisses {
        get => $this->get('blacklist_misses');
    }

    public ?int $blacklistMissRatio {
        get => $this->get('blacklist_miss_ratio');
    }

    public ?float $opcacheHitRate {
        get => $this->getFloat('opcache_hit_rate');
    }
}
