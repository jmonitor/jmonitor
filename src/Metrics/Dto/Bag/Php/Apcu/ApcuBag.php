<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Apcu;

use App\Metrics\Dto\Bag;

class ApcuBag extends Bag
{
    public private(set) ApcuConfigBag $config {
        get => $this->config ??= new ApcuConfigBag($this->all('config'));
    }

    public private(set) ApcuCacheBag $cache {
        get => $this->cache ??= new ApcuCacheBag($this->all('cache_info'));
    }

    public private(set) ApcuSmaBag $sma {
        get => $this->sma ??= new ApcuSmaBag($this->all('sma_info'));
    }
}
