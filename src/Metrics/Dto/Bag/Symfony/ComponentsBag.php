<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\Bag;

class ComponentsBag extends Bag
{
    public private(set) SchedulerBag $scheduler {
        get => $this->scheduler ??= new SchedulerBag($this->all('scheduler'));
    }

    public private(set) FlexRecipesBag $flexRecipes {
        get => $this->flexRecipes ??= new FlexRecipesBag($this->all('flex_recipes'));
    }

    public private(set) MessengerBag $messenger {
        get => $this->messenger ??= new MessengerBag($this->all('messenger'));
    }
}
