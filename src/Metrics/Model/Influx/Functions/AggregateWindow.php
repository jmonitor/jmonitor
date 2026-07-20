<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

use App\Chart\TimeRange;

readonly class AggregateWindow implements \Stringable
{
    public function __construct(
        private string $every,
        private string $fn = 'mean',
        private bool $createEmpty = true,
    ) {}

    public static function fromRange(TimeRange $range, string $fn = 'mean', bool $createEmpty = true): self
    {
        return new self($range->asWindowPeriod(), $fn, $createEmpty);
    }

    public function __toString(): string
    {
        $createEmpty = $this->createEmpty ? 'true' : 'false';

        return \sprintf('aggregateWindow(every: %s, fn: %s, createEmpty: %s)', $this->every, $this->fn, $createEmpty);
    }
}
