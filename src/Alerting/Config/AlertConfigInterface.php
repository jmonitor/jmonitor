<?php

declare(strict_types=1);

namespace App\Alerting\Config;

interface AlertConfigInterface
{
    /**
     * Returns the human-readable description of the value (for display in a list (table) for example).
     */
    public function getDescription(): ?string;
}
