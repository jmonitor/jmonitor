<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\MetricBagDto;

class PostgresSettingsBag extends MetricBagDto
{
    public ?string $serverVersion {
        get => $this->get('server_version');
    }

    public ?string $semanticServerVersion {
        get {
            $version = $this->get('server_version');

            if ($version === null) {
                return null;
            }

            $parts = explode(' ', $version);

            return $parts[0];
        }
    }

    public ?int $majorVersion {
        get {
            $semanticVersion = $this->semanticServerVersion;

            if ($semanticVersion === null) {
                return null;
            }

            $parts = explode('.', $semanticVersion);

            return (int) $parts[0];
        }
    }

    public ?int $maxConnections {
        get => $this->getInt('max_connections');
    }

    // Memory settings are sent in bytes by the collector (it converts from pg_settings units).
    public ?int $sharedBuffers {
        get => $this->getInt('shared_buffers');
    }

    public ?int $effectiveCacheSize {
        get => $this->getInt('effective_cache_size');
    }

    public ?int $workMem {
        get => $this->getInt('work_mem');
    }

    public ?int $maintenanceWorkMem {
        get => $this->getInt('maintenance_work_mem');
    }

    public ?string $walLevel {
        get => $this->get('wal_level');
    }

    public ?string $timeZone {
        get => $this->get('TimeZone');
    }

    public ?string $logMinDurationStatement {
        get => $this->get('log_min_duration_statement');
    }

    public ?string $autovacuum {
        get => $this->get('autovacuum');
    }

    public ?int $maxWalSize {
        get => $this->getInt('max_wal_size');
    }

    public ?string $checkpointCompletionTarget {
        get => $this->get('checkpoint_completion_target');
    }

    public ?string $randomPageCost {
        get => $this->get('random_page_cost');
    }

    public ?string $effectiveIoConcurrency {
        get => $this->get('effective_io_concurrency');
    }

    public ?string $trackCounts {
        get => $this->get('track_counts');
    }

    public ?string $autovacuumVacuumScaleFactor {
        get => $this->get('autovacuum_vacuum_scale_factor');
    }
}
