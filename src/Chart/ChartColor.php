<?php

declare(strict_types=1);

namespace App\Chart;

enum ChartColor: string
{
    case BLUE = '#6E94FF';
    case PINK = '#FE5F7E';
    case GREEN = '#17e3dc';
    case ORANGE = '#FFCF8E';
    case PURPLE = '#a855f7';
    case CYAN = '#06b6d4';
    case YELLOW = '#eab308';
    case INDIGO = '#6366f1';
    case RED = '#FF5F7E';

    /**
     * Returns the color at the given index (cycles when there are more datasets than colors).
     */
    public static function atIndex(int $index): self
    {
        $cases = self::cases();

        return $cases[$index % count($cases)];
    }
}
