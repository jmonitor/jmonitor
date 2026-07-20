<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Metric;

class GaugeDto extends AbstractDto
{
    /**
     * Used as a percentage for the chart.
     */
    public private(set) ?float $value = null;
    public private(set) int $min = 0;
    public private(set) int $max = 100;

    public function __construct(Metric $metric)
    {
        parent::__construct($metric);
    }

    public function setValue(?float $value, ?int $precision = null): static
    {
        $this->value = $value;

        if ($value !== null && $precision !== null) {
            $this->value = round($value, $precision);
        }

        return $this;
    }

    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
    }
}
