<?php

declare(strict_types=1);

namespace App\Metrics\Range\Dto;

use Traversable;

class Ranges implements \IteratorAggregate
{
    /**
     * @var Range[]
     */
    public private(set) array $ranges;
    public private(set) ?string $notes;

    public function __construct(array $ranges, ?string $notes = null)
    {
        $this->ranges = $ranges;
        $this->notes = $notes;
    }

    public function find(float|int $value): ?Range
    {
        foreach ($this->ranges as $range) {
            if ($value >= $range->min && $value < $range->max) {
                return $range;
            }

            // Handle the inclusive upper bound for the last range, e.g. when it is 100
            if ($value === (float) $range->max || $value === $range->max) {
                return $range;
            }
        }

        // runtime values can exceed the declared ranges, e.g. the CPU usage % can
        // actually go above 100%
        return null;
    }

    public function getIterator(): Traversable
    {
        yield from $this->ranges;
    }
}
