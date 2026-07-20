<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\System;

use App\Metrics\Dto\MetricBagDto;

class SystemBag extends MetricBagDto
{
    public private(set) DiskBag $disk {
        get => $this->disk ??= new DiskBag($this->all('disk'));
    }

    public private(set) CpuBag $cpu {
        get => $this->cpu ??= new CpuBag($this->all('cpu'));
    }

    public private(set) RamBag $ram {
        get => $this->ram ??= new RamBag($this->all('ram'));
    }

    public private(set) OsBag $os {
        get => $this->os ??= new OsBag($this->all('os'));
    }

    public ?int $time {
        get => $this->get('time');
    }

    public private(set) ?\DateTimeImmutable $dateTime {
        get => $this->dateTime ??= ($this->time ? new \DateTimeImmutable('@' . $this->time) : null);
    }

    public ?string $timeZone {
        get => $this->get('timezone');
    }

    public ?string $hostname {
        get => $this->get('hostname');
    }
}
