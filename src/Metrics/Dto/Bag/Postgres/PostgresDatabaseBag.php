<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\MetricBagDto;

class PostgresDatabaseBag extends MetricBagDto
{
    public ?string $schema { get => $this->get('schema'); }
    public ?int $dbSize { get => $this->getInt('db_size'); }

    public ?int $liveTuples { get => $this->tableInt('live_tuples'); }
    public ?int $deadTuples { get => $this->tableInt('dead_tuples'); }
    public ?int $seqScans { get => $this->tableInt('seq_scans'); }
    public ?int $idxScans { get => $this->tableInt('idx_scans'); }
    public ?int $totalSize { get => $this->tableInt('total_size'); }
    public ?int $indexesSize { get => $this->tableInt('indexes_size'); }

    public ?float $deadTupleRatio {
        get {
            if ($this->liveTuples === null || $this->deadTuples === null) {
                return null;
            }
            $total = $this->liveTuples + $this->deadTuples;

            return $total > 0 ? round($this->deadTuples / $total * 100, 2) : null;
        }
    }

    public ?float $indexUsageRatio {
        get {
            if ($this->seqScans === null || $this->idxScans === null) {
                return null;
            }
            $total = $this->seqScans + $this->idxScans;

            return $total > 0 ? round($this->idxScans / $total * 100, 2) : null;
        }
    }

    private function tableInt(string $key): ?int
    {
        $tables = $this->all('tables');

        return isset($tables[$key]) ? (int) $tables[$key] : null;
    }
}
