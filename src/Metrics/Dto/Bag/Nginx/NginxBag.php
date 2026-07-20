<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Nginx;

use App\Metrics\Dto\MetricBagDto;

class NginxBag extends MetricBagDto
{
    public ?string $nginxVersion {
        get => $this->get('version');
    }

    /**
     * @return string[]
     */
    public array $modules {
        get => $this->all('modules');
    }

    public NginxStatusBag $status {
        get => $this->status ??= new NginxStatusBag($this->all('status'), $this);
    }

    public ?NginxConfigBag $config {
        get => $this->config ??= new NginxConfigBag($this->all('config'), $this);
    }

    public ?int $cpuCount {
        get => $this->get('cpu_count');
    }
}
