<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Chart\Units\Bytes;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * A progress bar with a peak marker
 */
#[AsTwigComponent]
class Peak
{
    public int|float|Bytes $current;
    public bool $showCurrent = false;
    public int|Bytes $max;
    public int|float|Bytes $peak;

    #[ExposeInTemplate]
    public function getPercent(): int
    {
        return (int) round($this->currentValue() / $this->maxValue() * 100);
    }

    #[ExposeInTemplate]
    public function peakLeft(): ?float
    {
        return min(100, round($this->peakValue() / $this->maxValue() * 100, 2));
    }

    #[ExposeInTemplate]
    public function peakValue(): int|float
    {
        if ($this->peak instanceof Bytes) {
            return $this->peak->value();
        }

        return $this->peak;
    }

    #[ExposeInTemplate]
    public function maxValue(): int|float
    {
        if ($this->max instanceof Bytes) {
            return $this->max->value();
        }

        return $this->max;
    }

    #[ExposeInTemplate]
    public function currentValue(): int|float
    {
        if ($this->current instanceof Bytes) {
            return $this->current->value();
        }

        return $this->current;
    }
}
