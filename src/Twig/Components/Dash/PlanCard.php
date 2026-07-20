<?php

declare(strict_types=1);

namespace App\Twig\Components\Dash;

use App\Entity\Enums\Plan;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class PlanCard
{
    public Plan $plan;

}
