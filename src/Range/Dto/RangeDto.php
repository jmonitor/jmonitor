<?php

declare(strict_types=1);

namespace App\Range\Dto;

use App\Chart\TimeRange;

class RangeDto
{
    public private(set) TimeRange $range;

    public function __construct(?TimeRange $range = TimeRange::LAST_1_HOUR)
    {
        $this->range = $range ?? TimeRange::LAST_1_HOUR;
    }

    public function setRange(?TimeRange $range): void
    {
        $this->range = $range;
    }
}
