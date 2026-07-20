<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class StatusBag extends Bag
{
    public ?bool $enabled {
        get => $this->getBool('opcache_enabled');
    }

    public ?bool $cacheFull {
        get => $this->getBool('cache_full');
    }

    public ?bool $restartPending {
        get => $this->getBool('restart_pending');
    }

    public ?bool $restartInProgress {
        get => $this->getBool('restart_in_progress');
    }

    public private(set) ?MemoryBag $memory {
        get => $this->memory ??= new MemoryBag($this->all('memory_usage'));
    }

    public private(set) ?InternedStringsBag $internedStringsUsage {
        get => $this->internedStringsUsage ??= new InternedStringsBag($this->all('interned_strings_usage'));
    }

    public private(set) ?StatisticsBag $statistics {
        get => $this->statistics ??= new StatisticsBag($this->all('opcache_statistics'));
    }

    public private(set) ?JitBag $jit {
        get => $this->jit ??= new JitBag($this->all('jit'));
    }
}
