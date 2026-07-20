<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class InternedStringsBag extends Bag
{
    public ?int $bufferSize {
        get => $this->get('buffer_size');
    }

    public ?int $usedMemory {
        get => $this->get('used_memory');
    }

    public ?int $freeMemory {
        get => $this->get('free_memory');
    }

    public ?int $numberOfStrings {
        get => $this->get('number_of_strings');
    }
}
