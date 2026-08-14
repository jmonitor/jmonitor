<?php

declare(strict_types=1);

namespace App\Version;

/**
 * A JMonitor package an agent can advertise the version of. The value is part of the
 * update-check URL.
 */
enum Package: string
{
    case COLLECTOR = 'collector';
    case BUNDLE = 'bundle';

    public function repository(): string
    {
        return match ($this) {
            self::COLLECTOR => 'jmonitor/collector',
            self::BUNDLE => 'jmonitor/jmonitor-bundle',
        };
    }
}
