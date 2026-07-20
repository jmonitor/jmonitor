<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

use App\Chart\TimeRange as ChartRange;

class Range implements \Stringable
{
    public function __construct(private readonly string $start, private readonly ?string $stop = null) {}

    public static function fromRange(ChartRange $range): self
    {
        return new self((string) $range->asStartDateTime()->getTimestamp(), (string) $range->asEndDateTime()->getTimestamp());
    }

    public function __toString(): string
    {
        // if issues arise, fall back to: range(start: time(v: "%s"), stop: time(v: "%s"))     value :$range->asStartDateTime()->format(\DateTimeInterface::RFC3339)
        // could be optimized (stop is optional)
        return \sprintf('range(start: %s, stop: %s)', $this->start, $this->stop ?? 'now()');
    }
}
