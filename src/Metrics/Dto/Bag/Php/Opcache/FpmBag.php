<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class FpmBag extends Bag
{
    public int|false|null $memoryLimit;

    public function __construct(array $parameters, int|false|null $memoryLimit)
    {
        parent::__construct($parameters);

        $this->memoryLimit = $memoryLimit;
    }

    public ?string $pool {
        get => $this->get('pool');
    }

    public ?string $processManager {
        get => $this->get('process-manager');
    }

    public ?int $uptime {
        get => $this->get('start-since');
    }

    public ?int $acceptedConn {
        get => $this->get('accepted-conn');
    }

    public ?int $idleProcesses {
        get => $this->get('idle-processes');
    }

    public ?int $activeProcesses {
        get => $this->get('active-processes');
    }

    public ?int $totalProcesses {
        get => $this->idleProcesses !== null && $this->activeProcesses !== null ? $this->idleProcesses + $this->activeProcesses : null;
    }

    public ?int $maxActiveProcesses {
        get => $this->get('max-active-processes');
    }

    public private(set) ?float $processesUsagePercent = null {
        get {
            if ($this->processesUsagePercent !== null) {
                return $this->processesUsagePercent;
            }

            if ($this->totalProcesses >= 0 && $this->activeProcesses > 0) {
                return $this->processesUsagePercent = round(($this->activeProcesses / $this->totalProcesses) * 100, 2);
            }

            return null;
        }
    }

    public ?int $maxChildrenReached {
        get => $this->get('max-children-reached');
    }

    public ?int $slowRequests {
        get => $this->get('slow-requests');
    }

    public ?int $memoryPeak {
        get => $this->get('memory-peak');
    }

    // computed from a delta
    public ?float $reqPerSec {
        get => $this->getFloat('requests-per-sec');
    }

    public float|false|null $memoryPeakPercent {
        get {
            // not applicable since there is no max memory
            if ($this->memoryLimit === false) {
                return false;
            }

            if ($this->memoryLimit === null || $this->memoryLimit <= 0) {
                return null;
            }

            if ($this->memoryPeak === null) {
                return null;
            }

            return round($this->memoryPeak / $this->memoryLimit * 100);
        }
    }
}
