<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Error\DisplayMode;

/**
 * Defines what the card should display when there is no metric data to show
 */
enum EmptyDataMode: string
{
    /**
     * Just the "title" (basically the icon with "No data") and a "why" message linking to the help
     */
    case SIMPLE = 'simple';

    /**
     * The title plus the possible explanations
     */
    case EXTENDED = 'extended';
}
