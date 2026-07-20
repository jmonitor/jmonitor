<?php

namespace App\Twig\Components\Html\Metrics;

use App\Metrics\Dto\MetricBagDto;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class H2
{
    public ?string $helpItem = null;
    public bool $showLastUpdate = true;
    public bool $showRange = false;
    public ?MetricBagDto $bag = null;
}
