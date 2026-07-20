<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Metric;
use App\Metrics\Renderer\Error\DisplayMode\EmptyDataMode;
use App\Metrics\Renderer\Model\Badge\Badge;

abstract class AbstractDto implements MetricDtoInterface
{
    public private(set) bool $valueAvailable = true;
    public private(set) ?EmptyDataMode $emptyDataMode = null;
    public private(set) Metric $metric;
    public private(set) ?string $cardTemplate = null;
    public private(set) ?string $cardTitle = null;
    public private(set) ?Badge $badge = null;

    /** @var mixed[] key value pair for template */
    public private(set) array $context = [];

    public function __construct(Metric $metric)
    {
        $this->metric = $metric;
    }

    public function setCardTemplate(?string $cardTemplate): static
    {
        $this->cardTemplate = $cardTemplate;

        return $this;
    }

    public function setBadge(?Badge $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function setCardTitle(?string $cardTitle): static
    {
        $this->cardTitle = $cardTitle;

        return $this;
    }

    /**
     * REQUIREMENTS NOT SETTLED YET.
     * Returns true when the content of the metric card can be displayed.
     * It does not mean a value *will* necessarily be there, only that the value can potentially be retrieved.
     *
     * => It also avoids scattering many "if" checks throughout the computation/display process.
     */
    public function setValueAvailable(bool $valueAvailable, ?EmptyDataMode $mode = null): static
    {
        $this->valueAvailable = $valueAvailable;
        $this->emptyDataMode = $mode;

        return $this;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }
}
