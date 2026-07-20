<?php

declare(strict_types=1);

namespace App\Chart\Gauge;

enum Color: string
{
    case INFO = '#A8BFFF';
    case HEALTHY = '#17e3dc';
    case WARNING = '#FFCF8E';
    case DANGER = '#FF5F7E';
}
