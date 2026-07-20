<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

/**
 * Computes "per second" (by default).
 * If this default ever needs to change, this class can be modified to accept the unit.
 * https://docs.influxdata.com/flux/v0/stdlib/universe/derivative/
 */
readonly class Derivative implements \Stringable
{
    public function __construct(
        private string $unit = '1s',
        private bool $nonNegative = false,
    ) {}

    public function __toString(): string
    {
        $nonNegative = $this->nonNegative ? 'true' : 'false';

        return sprintf('derivative(unit: %s, nonNegative: %s)', $this->unit, $nonNegative);
    }
}
