<?php

declare(strict_types=1);

namespace App\Chart\Dto;

use App\Chart\Gauge\Color;

class GaugeChartConfiguration implements ChartConfigurationInterface
{
    public private(set) float $aspectRatio = 1.7;
    public private(set) string $color = Color::INFO->value;

    public function setAspectRatio(float $aspectRatio): static
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }

    public function setColor(string|Color $color): static
    {
        $color = $color instanceof Color ? $color->value : $color;
        $this->color = $color;

        return $this;
    }
}
