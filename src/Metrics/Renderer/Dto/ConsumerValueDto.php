<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;

class ConsumerValueDto extends AbstractDto
{
    public private(set) Consumer $consumer;
    public private(set) MetricBagDto $bag;
    public private(set) mixed $value;
    public private(set) ?\Closure $formatter = null;

    public function __construct(Metric $metric, Consumer $consumer, MetricBagDto $bag, mixed $value)
    {
        parent::__construct($metric);

        $this->consumer = $consumer;
        $this->bag = $bag;
        $this->value = $value;
    }

    public function formatValue(\Closure $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }
}
