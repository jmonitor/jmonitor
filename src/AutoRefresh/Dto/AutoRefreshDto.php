<?php

declare(strict_types=1);

namespace App\AutoRefresh\Dto;

class AutoRefreshDto
{
    public private(set) bool $autoRefresh = false;

    public function __construct(?bool $autoRefresh)
    {
        $this->autoRefresh = $autoRefresh ?? false;
    }

    public function setAutoRefresh(?bool $autoRefresh): void
    {
        $this->autoRefresh = $autoRefresh ?? false;
    }
}
