<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Metric;
use Twig\Markup;

class BasicDto extends AbstractDto
{
    /**
     * Can be anything -> available in the template via dto.value
     */
    public private(set) mixed $value = null;

    public private(set) ?Markup $formattedValue = null;

    public function __construct(Metric $metric)
    {
        parent::__construct($metric);
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function setFormattedValue(?Markup $value): static
    {
        $this->formattedValue = $value;

        return $this;
    }
}
