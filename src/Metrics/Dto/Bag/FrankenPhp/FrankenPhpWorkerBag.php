<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\FrankenPhp;

use App\Metrics\Dto\Bag;

class FrankenPhpWorkerBag extends Bag
{
    public ?string $name {
        get => $this->get('name');
    }

    public ?string $shortName {
        get {
            if ($this->name === null) {
                return null;
            }

            $shortName = $this->name;

            // it's a path
            if (str_ends_with($this->name, '.php')) {
                $parts = explode('/', $this->name);

                $shortName = end($parts);
            }

            return mb_substr($shortName, -30);
        }
    }

    public ?int $totalWorker {
        get => $this->get('total_workers');
    }

    public ?int $busyWorker {
        get => $this->get('busy_workers');
    }

    public ?float $busyWorkerPercent {
        get => $this->busyWorker !== null && $this->totalWorker > 0 ? round($this->busyWorker / $this->totalWorker * 100, 2) : null;
    }

    public ?float $workerRequestTime {
        get => $this->getFloat('worker_request_time');
    }

    public ?int $workerRequestCount {
        get => $this->getInt('worker_request_count');
    }

    /**
     * computed from the workerRequestCount delta
     */
    public ?float $workerReqPerSec {
        get => $this->getFloat('worker_request_per_sec');
    }

    /**
     * Average script execution time since the process (or worker, or thread) last started
     */
    public ?float $workerRequestTimeAvg {
        get => $this->workerRequestTime !== null && $this->workerRequestCount > 0 ? round($this->workerRequestTime / $this->workerRequestCount, 2) : null;
    }

    /**
     * computed from the workerRequestTime and workerRequestCount deltas
     * Warning, this value is not rounded (it is in seconds, and millisecond precision matters)
     */
    public ?float $realWorkerRequestTimeAvg {
        get => $this->get('real_worker_request_time_avg');
    }

    public ?int $realWorkerRequestTimeAvgMs {
        get => $this->realWorkerRequestTimeAvg !== null ? (int) round($this->realWorkerRequestTimeAvg * 1000) : null;
    }

    public ?int $readyWorkers {
        get => $this->get('ready_workers');
    }

    public ?int $workerRestarts {
        get => $this->get('worker_restarts');
    }
}
