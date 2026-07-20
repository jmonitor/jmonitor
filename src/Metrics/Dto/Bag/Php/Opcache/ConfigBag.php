<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class ConfigBag extends Bag
{
    public private(set) DirectivesBag $directives {
        get => $this->directives ??= new DirectivesBag($this->all('directives'));
    }

    public private(set) VersionBag $version {
        get => $this->version ??= new VersionBag($this->all('version'));
    }

    // the payload also contains a 'blacklist' key (empty array), deliberately not mapped
}
