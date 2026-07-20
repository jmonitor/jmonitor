<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\Bag;

class MysqlSlowQueryBag extends Bag
{
    public ?string $querySample {
        get => $this->get('query_sample');
    }

    public ?int $execCount {
        get => $this->getInt('exec_count');
    }

    public ?int $totalTimeMs {
        get => $this->getInt('total_time_ms');
    }

    public ?string $totalTimeS {
        get {
            $ms = $this->totalTimeMs;
            return $ms !== null ? sprintf('%.2f', $ms / 1000) : null;
        }
    }

    public ?int $avgTimeMs {
        get => $this->getInt('avg_time_ms');
    }

    public ?int $maxTimeMs {
        get => $this->getInt('max_time_ms');
    }
}
