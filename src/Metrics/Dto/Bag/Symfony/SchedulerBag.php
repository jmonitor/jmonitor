<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\Bag;

class SchedulerBag extends Bag
{
    /**
     * @var SchedulerTaskBag[]
     */
    public private(set) array $tasks {
        get {
            if (isset($this->tasks)) {
                return $this->tasks;
            }

            $this->tasks = [];
            foreach ($this->all() as $item) {
                $this->tasks[] = new SchedulerTaskBag($item);
            }

            // tri $this->scheduler par "next_run"
            usort($this->tasks, fn(SchedulerTaskBag $a, SchedulerTaskBag $b) => $a->nextRun <=> $b->nextRun);

            return $this->tasks;
        }
    }

    public ?SchedulerTaskBag $nextTask {
        get {
            return $this->tasks[0] ?? null;
        }
    }
}
