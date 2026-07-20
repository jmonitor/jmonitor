<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\MetricBagDto;

class RedisBag extends MetricBagDto
{
    public private(set) ServerBag $server {
        get => $this->server ??= new ServerBag($this->all('server'));
    }

    public private(set) ClientsBag $clients {
        get => $this->clients ??= new ClientsBag($this->all('clients'));
    }

    public private(set) MemoryBag $memory {
        get => $this->memory ??= new MemoryBag($this->all('memory'));
    }

    public private(set) PersistenceBag $persistence {
        get => $this->persistence ??= new PersistenceBag($this->all('persistence'));
    }

    public private(set) StatsBag $stats {
        get => $this->stats ??= new StatsBag($this->all('stats'));
    }

    public private(set) ReplicationBag $replication {
        get => $this->replication ??= new ReplicationBag($this->all('replication'));
    }

    public private(set) CpuBag $cpu {
        get => $this->cpu ??= new CpuBag($this->all('cpu'));
    }

    public private(set) ConfigBag $config {
        get => $this->config ??= new ConfigBag($this->all('config'));
    }

    /**
     * @var DatabaseBag[]
     */
    public private(set) array $databases {
        get => $this->databases ?? $this->initDatabases();
    }

    public function getDatabase(int $index): ?DatabaseBag
    {
        return $this->databases[$index] ?? null;
    }

    /**
     * @return DatabaseBag[]
     * The key names ("db0", etc.) are not kept, just in case
     */
    private function initDatabases(): array
    {
        $items = [];
        foreach ($this->all('databases') as $db) {
            if (\is_array($db)) {
                $items[] = new DatabaseBag($db);
            }
        }

        return $items;
    }
}
