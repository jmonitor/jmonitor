<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Renderer\Error\DisplayMode\EmptyDataMode;

enum Renderer: string
{
    case Gauge = 'gauge';
    case Line = 'line';
    case Bar = 'bar';
    case ConsumerValue = 'consumer_value';
    case Basic = 'basic';

    public function supportRange(): bool
    {
        return match ($this) {
            self::Line,
            self::Bar => true,
            default => false,
        };
    }

    /**
     * Public name
     */
    public function styleLabel(): string
    {
        return match ($this) {
            self::Gauge => 'Gauge',
            self::Line => 'Line',
            self::Bar => 'Bar',
            self::ConsumerValue => 'Raw value',
            self::Basic => 'Basic', // Raw value
        };
    }

    /**
     * What should be displayed when the renderer reports there is no data to render?
     */
    public function getDefaultEmptyDataMode(): EmptyDataMode
    {
        return match ($this) {
            self::Line,
            self::Bar => EmptyDataMode::EXTENDED,
            default => EmptyDataMode::SIMPLE,
        };
    }
}
