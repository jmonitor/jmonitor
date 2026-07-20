<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Metric
{
    /**
     * enum Metric
     */
    public string $metric;

    /**
     * enum Renderer
     */
    public ?string $renderer = null;

    /**
     * Options defined in the metric's service.
     */
    public ?array $options = [];
}
