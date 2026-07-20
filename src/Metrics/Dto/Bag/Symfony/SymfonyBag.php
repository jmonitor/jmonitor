<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\MetricBagDto;

class SymfonyBag extends MetricBagDto
{
    public ?string $env {
        get => $this->get('env');
    }

    public ?bool $debug {
        get => $this->getBool('debug');
    }

    public ?string $symfonyVersion {
        get => $this->get('version');
    }

    /**
     * @var string[]
     */
    public array $bundles {
        get => $this->all('bundles');
    }

    public ?string $projectDir {
        get => $this->get('project_dir');
    }

    public ?string $cacheDir {
        get => $this->get('cache_dir');
    }

    public ?string $logDir {
        get => $this->get('log_dir');
    }

    public ?string $buildDir {
        get => $this->get('build_dir');
    }

    public ?string $shareDir {
        get => $this->get('share_dir');
    }

    public ?string $charset {
        get => $this->get('charset');
    }

    public private(set) ComponentsBag $components {
        get => $this->components ??= new ComponentsBag($this->all('components'));
    }
}
