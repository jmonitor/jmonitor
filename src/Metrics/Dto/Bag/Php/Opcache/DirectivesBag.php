<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class DirectivesBag extends Bag
{
    public ?bool $enabled {
        get => $this->getBool('opcache.enable');
    }

    public ?int $memoryConsumption {
        get => $this->getInt('opcache.memory_consumption');
    }

    public ?bool $enabledCli {
        get => $this->getBool('opcache.enable_cli');
    }

    public ?bool $validateTimestamps {
        get => $this->getBool('opcache.validate_timestamps');
    }

    public ?int $maxAcceleratedFiles {
        get => $this->getInt('opcache.max_accelerated_files');
    }

    public ?int $revalidateFrequency {
        get => $this->getInt('opcache.revalidate_freq');
    }

    public ?string $preload {
        get => $this->get('opcache.preload');
    }
}
