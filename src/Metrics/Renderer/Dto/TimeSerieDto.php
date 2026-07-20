<?php

namespace App\Metrics\Renderer\Dto;

class TimeSerieDto extends AbstractDto
{
    public private(set) ?string $measurement = null;
    public private(set) ?\Closure $queryBuilder = null;
    public private(set) ?string $unit = null;

    /**
     * @var string[]
     */
    public private(set) array $fields;

    /**
     * 0: the field value
     * 1: the unit
     * If set, it is displayed as-is.
     * @var mixed[]
     */
    public private(set) array $currentValue = [];

    public function setMeasurement(string $measurement): self
    {
        $this->measurement = $measurement;

        return $this;
    }

    public function setField(string $field, ?string $label = null): self
    {
        return $this->setFields([$field => $label ?? '']);
    }

    /**
     * key: the field name in InfluxDB
     * value: the chart label for this field
     * @param array<string, string> $fields
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function setQueryBuilder(\Closure $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function getFieldLabel(string $field): ?string
    {
        return $this->fields[$field] ?? null;
    }

    public function setCurrentValue(mixed $currentValue, ?string $unit = null): self
    {
        $this->currentValue = $currentValue !== null ? [$currentValue, $unit] : [];

        return $this;
    }
}
