<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class StatsBag extends Bag
{
    public ?int $totalConnectionsReceived { get => $this->getInt('total_connections_received'); }
    public ?int $totalCommandsProcessed { get => $this->getInt('total_commands_processed'); }

    // not displayed: too noisy, $opsPerSec below is preferred
    public ?int $instantaneousOpsPerSec { get => $this->getInt('instantaneous_ops_per_sec'); }

    // computed from the "total_commands_processed" delta
    public ?float $opsPerSec { get => $this->get('ops_per_sec'); }

    public ?int $rejectedConnections { get => $this->getInt('rejected_connections'); }
    public ?int $expiredKeys { get => $this->getInt('expired_keys'); }
    public ?int $evictedKeys { get => $this->getInt('evicted_keys'); }
    public ?int $evictedClients { get => $this->getInt('evicted_clients'); }
    public ?int $keyspaceHits { get => $this->getInt('keyspace_hits'); }
    public ?int $keyspaceMisses { get => $this->getInt('keyspace_misses'); }
    public ?int $trackingTotalKeys { get => $this->getInt('tracking_total_keys'); }
    public ?int $totalErrorReplies { get => $this->getInt('total_error_replies'); }
    public ?int $totalReadsProcessed { get => $this->getInt('total_reads_processed'); }
    public ?int $totalWritesProcessed { get => $this->getInt('total_writes_processed'); }
    public ?int $aclAccessDeniedAuth { get => $this->getInt('acl_access_denied_auth'); }

    public ?float $hitRate {
        get {
            if ($this->keyspaceHits === null || $this->keyspaceMisses <= 0) {
                return null;
            }

            return round(($this->keyspaceHits / ($this->keyspaceHits + $this->keyspaceMisses)) * 100, 2);
        }
    }
}
