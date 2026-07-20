<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class ConfigBag extends Bag
{
    // ex : "900 1 300 10 60 10000"
    public ?string $save {
        get => $this->get('save');
    }

    public ?bool $rdbSaveEnabled {
        get {
            if ($this->save === null) {
                return null;
            }

            return (bool) $this->save;
        }
    }
}
