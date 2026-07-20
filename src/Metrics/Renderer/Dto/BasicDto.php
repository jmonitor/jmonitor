<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Metric;

class BasicDto extends AbstractDto
{
    /**
     * Can be anything -> available in the template via dto.value
     */
    public private(set) mixed $value = null;

    /**
     * If there is no custom template, this can be used instead; it is displayed as-is.
     * @var ?scalar $formattedValue
     */
    public private(set) mixed $formattedValue = null;

    public function __construct(Metric $metric)
    {
        parent::__construct($metric);
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function setFormattedValue(mixed $value): static
    {
        $this->formattedValue = $value;

        return $this;
    }
}
