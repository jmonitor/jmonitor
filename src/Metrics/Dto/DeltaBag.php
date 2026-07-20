<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

class DeltaBag
{
    private readonly Bag $previousBag;
    private readonly Bag $currentBag;
    public private(set) readonly int $elapsedTime;

    // property cache for the current script
    private array $deltasByKey = [];

    public function __construct(
        array $previousValues,
        array $currentValues,
        int $elapsedTime,
    ) {
        $this->previousBag = new Bag($previousValues);
        $this->currentBag = new Bag($currentValues);
        $this->elapsedTime = $elapsedTime;
    }

    public function getValue(string $key): int|float|null
    {
        if (array_key_exists($key, $this->deltasByKey)) {
            return $this->deltasByKey[$key];
        }

        $current = $this->currentBag->get($key);
        $previous = $this->previousBag->get($key);

        if (!is_numeric($current) || !is_numeric($previous)) {
            return $this->deltasByKey[$key] = null;
        }

        $delta = $current - $previous;

        if ($delta < 0) {
            return $this->deltasByKey[$key] = null;
        }

        return $this->deltasByKey[$key] = $delta;
    }

    public function getPerSec(string $key): ?float
    {
        $delta = $this->getValue($key);

        return $delta === null ? null : round($delta / $this->elapsedTime, 2);
    }
}
