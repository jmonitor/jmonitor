<?php

declare(strict_types=1);

namespace App\Chart\Dto;

use App\Chart\ChartColor;
use App\Chart\TimeRange;
use App\Chart\Units\Unit;
use Symfony\UX\Chartjs\Model\Chart;

class TimeSeriesChartConfiguration implements ChartConfigurationInterface
{
    public const float DEFAULT_ASPECT_RATIO = 2.8;

    public private(set) string $chartType = Chart::TYPE_LINE;
    public private(set) ?int $yMin = null;
    public private(set) ?int $suggestedYMin = null;
    public private(set) ?int $yMax = null;
    public private(set) ?TimeRange $range = null;
    public private(set) ?float $aspectRatio = self::DEFAULT_ASPECT_RATIO;
    public private(set) ?Unit $unit = null;

    /**
     * Custom colors per field (key = field name, value = color).
     * If not set, the default palette is used.
     *
     * @var array<string, ChartColor>
     */
    public private(set) array $colors = [];

    /**
     * Sets a custom color for one or more fields.
     *
     * @param array<string, ChartColor> $colors key = field name, value = color
     */
    public function setColors(array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function setColor(string $field, ChartColor $color): self
    {
        $this->colors[$field] = $color;

        return $this;
    }

    public function setChartType(string $type): self
    {
        $this->chartType = $type;

        return $this;
    }

    public function setYMin(?int $yMin): self
    {
        $this->yMin = $yMin;

        return $this;
    }

    public function setSuggestedYMin(?int $suggestedYMin): self
    {
        $this->suggestedYMin = $suggestedYMin;

        return $this;
    }

    public function setYMax(?int $yMax): self
    {
        $this->yMax = $yMax;

        return $this;
    }

    public function setRange(?TimeRange $range): self
    {
        $this->range = $range;

        return $this;
    }

    public function setAspectRatio(?float $aspectRatio): static
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }
}
