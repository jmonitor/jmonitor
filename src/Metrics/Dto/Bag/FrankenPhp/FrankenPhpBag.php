<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\FrankenPhp;

use App\Metrics\Dto\MetricBagDto;

class FrankenPhpBag extends MetricBagDto
{
    public ?string $frankenPhpVersion {
        get => $this->get('version');
    }

    public ?string $mode {
        get => $this->get('mode');
    }

    public ?int $busyThreads {
        get => $this->getInt('busy_threads');
    }

    public ?int $totalThreads {
        get => $this->getInt('total_threads');
    }

    public ?float $busyThreadsPercent {
        get => $this->busyThreads !== null && $this->totalThreads > 0 ? round($this->busyThreads / $this->totalThreads * 100, 2) : null;
    }

    public ?int $queueDepth {
        get => $this->getInt('queue_depth');
    }

    /**
     * In worker mode, each started worker is a "thread" considered busy, even if the worker does nothing.
     * Returns the number of threads taken up by workers (worker mode)
     */
    public ?int $totalWorkersThreads {
        get {
            if (empty($this->workers)) {
                return null;
            }

            return array_sum(array_map(fn(FrankenPhpWorkerBag $w) => $w->totalWorker ?? 0, $this->workers));
        }
    }

    public ?int $workerCrashes {
        get => $this->getInt('worker_crashes');
    }

    public ?int $workerQueueDepth {
        get => $this->getInt('worker_queue_depth');
    }

    /**
     * @var FrankenPhpWorkerBag[]
     */
    public private(set) array $workers {
        get => $this->workers ?? $this->initWorkers();
    }

    public function getWorker(int $index): ?FrankenPhpWorkerBag
    {
        return $this->workers[$index] ?? null;
    }

    /**
     * @return FrankenPhpWorkerBag[]
     */
    private function initWorkers(): array
    {
        $items = [];
        foreach ($this->all('workers') as $worker) {
            if (\is_array($worker)) {
                $items[] = new FrankenPhpWorkerBag($worker);
            }
        }

        return $items;
    }
}
