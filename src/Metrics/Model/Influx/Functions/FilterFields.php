<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

class FilterFields implements \Stringable
{
    public function __construct(
        /**
         * @var string[]
         */
        private readonly array $fields,
    ) {}

    /**
     * @param string[] $fields
     */
    public static function fields(array $fields): self
    {
        return new self($fields);
    }

    public function __toString(): string
    {
        $value = \implode(' or ', array_map(fn(string $field): string => \sprintf('r["_field"] == "%s"', $field), $this->fields));

        return \sprintf('filter(fn: (r) => %s)', $value);
    }
}
