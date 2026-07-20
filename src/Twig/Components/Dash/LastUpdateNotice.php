<?php

declare(strict_types=1);

namespace App\Twig\Components\Dash;

use App\Metrics\Dto\MetricBagDto;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class LastUpdateNotice
{
    public MetricBagDto $bag;
}
