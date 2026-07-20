<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

class Filter implements \Stringable
{
    public function __construct(
        private readonly string $column,
        private readonly string|float|int $value,
        private readonly string $operator = '==',
    ) {}

    public static function mesurement(string $name): self
    {
        return new self('_measurement', $name);
    }

    public static function field(string $name): self
    {
        return new self('_field', $name);
    }

    /**
     * @param string[] $fields
     */
    public static function fields(array $fields): FilterFields
    {
        return FilterFields::fields($fields);
    }

    public function __toString(): string
    {
        if (is_string($this->value)) {
            return \sprintf('filter(fn: (r) => r["%s"] %s "%s")', $this->column, $this->operator, $this->value);
        }

        return \sprintf('filter(fn: (r) => r["%s"] %s %s)', $this->column, $this->operator, $this->value);
    }
}
