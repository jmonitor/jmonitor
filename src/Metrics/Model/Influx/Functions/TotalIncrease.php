<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

/**
 * Total growth of a cumulative counter, a reset counting from zero again.
 */
readonly class TotalIncrease implements \Stringable
{
    public function __toString(): string
    {
        return 'difference(nonNegative: true) |> sum()';
    }
}
