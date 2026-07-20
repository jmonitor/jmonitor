<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx;

use App\Chart\TimeRange as ChartRange;
use App\Metrics\Model\Influx\Functions\AggregateWindow;
use App\Metrics\Model\Influx\Functions\Derivative;
use App\Metrics\Model\Influx\Functions\Filter;
use App\Metrics\Model\Influx\Functions\FilterFields;
use App\Metrics\Model\Influx\Functions\From;
use App\Metrics\Model\Influx\Functions\Range;

class QueryBuilder implements \Stringable
{
    private From $from;
    private ?Range $range = null;
    private ?Filter $measurement = null;
    private Filter|FilterFields|null $field = null;
    /** @var array<string, Filter> */
    private array $tags = [];
    private ?Derivative $derivative = null;
    private ?AggregateWindow $aggregateWindow = null;

    public function __construct(string $bucketName)
    {
        $this->from = new From($bucketName);
    }

    public function range(ChartRange $range): self
    {
        $this->range = Range::fromRange($range);

        return $this;
    }

    public function measurement(string $name): self
    {
        $this->measurement = Filter::mesurement($name);

        return $this;
    }

    public function field(string $name): self
    {
        $this->field = Filter::field($name);

        return $this;
    }

    public function tag(string $name, mixed $value): self
    {
        $this->tags[$name] = new Filter($name, (string) $value);

        return $this;
    }

    /**
     * @param string[] $fields
     */
    public function fields(array $fields): self
    {
        if (count($fields) === 1) {
            return $this->field($fields[0]);
        }

        $this->field = Filter::fields($fields);

        return $this;
    }

    public function aggregateWindow(ChartRange $range, string $fn = 'mean'): self
    {
        $this->aggregateWindow = AggregateWindow::fromRange($range, $fn);

        return $this;
    }

    public function derivative(string $unit = '1s', bool $nonNegative = false): self
    {
        $this->derivative = new Derivative($unit, $nonNegative);

        return $this;
    }

    public function getQuery(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        $parts = [$this->from];

        if ($this->range !== null) {
            $parts[] = $this->range;
        }

        if ($this->measurement !== null) {
            $parts[] = $this->measurement;
        }

        if ($this->field !== null) {
            $parts[] = $this->field;
        }

        foreach ($this->tags as $tag) {
            $parts[] = $tag;
        }

        if ($this->derivative !== null) {
            $parts[] = $this->derivative;
        }

        if ($this->aggregateWindow !== null) {
            $parts[] = $this->aggregateWindow;
        }

        return \implode(' |> ', $parts);
    }
}
