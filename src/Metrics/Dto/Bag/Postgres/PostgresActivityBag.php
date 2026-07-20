<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\MetricBagDto;

class PostgresActivityBag extends MetricBagDto
{
    // --- database_stats ---
    public ?int $numbackends { get => $this->statInt('numbackends'); }
    public ?int $xactCommit { get => $this->statInt('xact_commit'); }
    public ?int $xactRollback { get => $this->statInt('xact_rollback'); }
    public ?int $blksRead { get => $this->statInt('blks_read'); }
    public ?int $blksHit { get => $this->statInt('blks_hit'); }
    public ?int $tupInserted { get => $this->statInt('tup_inserted'); }
    public ?int $tupUpdated { get => $this->statInt('tup_updated'); }
    public ?int $tupDeleted { get => $this->statInt('tup_deleted'); }
    public ?int $deadlocks { get => $this->statInt('deadlocks'); }
    public ?int $tempFiles { get => $this->statInt('temp_files'); }
    public ?int $tempBytes { get => $this->statInt('temp_bytes'); }

    public ?float $cacheHitRatio {
        get {
            if ($this->blksHit === null || $this->blksRead === null) {
                return null;
            }
            $total = $this->blksHit + $this->blksRead;

            return $total > 0 ? round($this->blksHit / $total * 100, 2) : null;
        }
    }

    public ?float $rollbackRatio {
        get {
            if ($this->xactCommit === null || $this->xactRollback === null) {
                return null;
            }
            $total = $this->xactCommit + $this->xactRollback;

            return $total > 0 ? round($this->xactRollback / $total * 100, 2) : null;
        }
    }

    public ?float $transactionsPerSecond { get => $this->getFloat('transactions_per_second'); }
    public ?float $deadlocksPerSecond { get => $this->getFloat('deadlocks_per_second'); }

    /** @return array<string, int> */
    public array $connections {
        get => array_map(static fn($v): int => (int) $v, $this->all('connections'));
    }

    // --- sessions ---
    public ?int $oldestTransactionSeconds { get => $this->sessionInt('oldest_transaction_seconds'); }
    public int $idleInTransactionCount { get => $this->sessionInt('idle_in_transaction_count') ?? 0; }
    public ?int $oldestIdleInTransactionSeconds { get => $this->sessionInt('oldest_idle_in_transaction_seconds'); }
    public int $blockedCount { get => $this->sessionInt('blocked_count') ?? 0; }
    public ?int $maxWaitSeconds { get => $this->sessionInt('max_wait_seconds'); }

    /** @return array<int, array<string, mixed>> */
    public array $blockedQueries {
        get => $this->all('sessions')['blocked_queries'] ?? [];
    }

    private function statInt(string $key): ?int
    {
        $stats = $this->all('database_stats');

        return isset($stats[$key]) ? (int) $stats[$key] : null;
    }

    private function sessionInt(string $key): ?int
    {
        $sessions = $this->all('sessions');

        return isset($sessions[$key]) ? (int) $sessions[$key] : null;
    }
}
