<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\Bag;

class FlexRecipesBag extends Bag
{
    public ?bool $upToDate {
        get => $this->getBool('up_to_date');
    }

    /**
     * @return string[]
     */
    public array $outdatedRecipes {
        get => $this->all('outdated_recipes');
    }
}
