<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Apcu;

use App\Metrics\Dto\Bag;

class ApcuConfigBag extends Bag
{
    public ?bool $enabled {
        get => $this->getBool('apc.enabled');
    }

    public ?int $shmSize {
        get => $this->getIniParsedInt('apc.shm_size');
    }

    public ?int $shmSegments {
        get => $this->get('apc.shm_segments');
    }

    public ?int $ttl {
        get => $this->get('apc.ttl');
    }

    public ?bool $enabledCli {
        get => $this->getBool('apc.enable_cli');
    }
}
