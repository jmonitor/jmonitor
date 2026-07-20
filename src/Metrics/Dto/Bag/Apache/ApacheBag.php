<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Apache;

use App\Metrics\Dto\MetricBagDto;

class ApacheBag extends MetricBagDto
{
    // ex Apache/2.4.62 (Win64) OpenSSL..
    public ?string $serverVersion {
        get => $this->get('server_version');
    }

    // 2.4.62
    public ?string $apacheVersionNumber {
        get {
            return $this->apacheVersionNumber ??= preg_match('~\bApache/(\d+(?:\.\d+){1,3})(?=[^\d]|$)~i', $this->serverVersion ?? '', $m) === 1 ? $m[1] : null;
        }
    }

    public ?string $serverMpm {
        get => $this->get('server_mpm');
    }

    public ?int $uptime {
        get => $this->get('uptime');
    }

    public ?float $load1 {
        get => $this->getFloat('load1');
    }

    public ?float $load5 {
        get => $this->getFloat('load5');
    }

    public ?float $load15 {
        get => $this->getFloat('load15');
    }

    // average requests/s, computed from the "total_accesses" delta
    public ?float $realRequestsPerSecond {
        get => $this->getFloat('real_requests_per_second');
    }

    // average, computed from the "total_bytes" delta
    public ?float $realBytesPerSecond {
        get => $this->getFloat('real_bytes_per_second');
    }

    // raw mod_status counter since the last restart, not displayed: the delta-based real* values above are used instead
    public ?int $totalAccesses {
        get => $this->get('total_accesses');
    }

    // since restart, not displayed
    public ?int $totalBytes {
        get => $this->get('total_bytes');
    }

    // since restart, not displayed
    public ?int $requestsPerSecond {
        get => $this->get('requests_per_second');
    }

    // since restart, not displayed
    public ?int $bytesPerSecond {
        get => $this->get('bytes_per_second');
    }

    // since restart, not displayed
    public ?int $bytesPerRequest {
        get => $this->get('bytes_per_request');
    }

    // since restart, not displayed
    public ?int $durationPerRequest {
        get => $this->get('duration_per_request');
    }

    public private(set) WorkersBag $workers {
        get => $this->workers ??= new WorkersBag($this->all('workers'));
    }

    public private(set) ScoreboardBag $scoreboard {
        get => $this->scoreboard ??= new ScoreboardBag($this->all('scoreboard'));
    }

    /**
     * @var string[]
     */
    public array $modules {
        get => $this->all('modules');
    }
}
