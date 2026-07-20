<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\MetricBagDto;

class MysqlStatusBag extends MetricBagDto
{
    public ?int $abortedClients {
        get => $this->getInt('Aborted_clients');
    }

    public ?int $abortedConnects {
        get => $this->getInt('Aborted_connects');
    }

    public ?int $comDelete {
        get => $this->getInt('Com_delete');
    }

    public ?int $comInsert {
        get => $this->getInt('Com_insert');
    }

    public ?int $comSelect {
        get => $this->getInt('Com_select');
    }

    public ?int $comUpdate {
        get => $this->getInt('Com_update');
    }

    public ?int $maxUsedConnections {
        get => $this->getInt('Max_used_connections');
    }

    public ?int $questions {
        get => $this->getInt('Questions');
    }

    /**
     * Questions delta
     */
    public ?float $questionsPerSecond {
        get => $this->getFloat('questions_per_second');
    }

    public ?int $threadsConnected {
        get => $this->getInt('Threads_connected');
    }

    public ?int $threadsRunning {
        get => $this->getInt('Threads_running');
    }

    public ?int $uptime {
        get => $this->getInt('Uptime');
    }

    // total number of connection attempts to the MySQL server since startup (successful or not).
    public ?int $connections {
        get => $this->getInt('Connections');
    }

    // number of temporary tables that had to be created on disk (often due to insufficient memory or heavy queries).
    public ?int $createdTmpDiskTables {
        get => $this->getInt('Created_tmp_disk_tables');
    }

    // total number of temporary tables created (in memory and/or on disk).
    public ?int $createdTmpTables {
        get => $this->getInt('Created_tmp_tables');
    }

    // % of temporary tables created on disk relative to all of them (disk + memory)
    public ?float $createdTmpDiskTablesPercent {
        get => $this->createdTmpDiskTables !== null && $this->createdTmpTables > 0 ? round($this->createdTmpDiskTables / $this->createdTmpTables * 100, 2) : null;
    }

    // amount of data (in bytes) currently stored in the InnoDB buffer pool (pages holding useful data).
    public ?int $innodbBufferPoolBytesData {
        get => $this->getInt('Innodb_buffer_pool_bytes_data');
    }

    // exists on MariaDB but not on MySQL
    public ?int $innodbBufferPoolBytesFree {
        get => $this->getInt('Innodb_buffer_pool_bytes_free');
    }

    public ?int $innodbBufferPoolPagesTotal {
        get => $this->getInt('Innodb_buffer_pool_pages_total');
    }

    public ?int $innodbBufferPoolPagesFree {
        get => $this->getInt('Innodb_buffer_pool_pages_free');
    }

    public ?int $innodbBufferPoolPagesUsed {
        get => $this->innodbBufferPoolPagesTotal !== null && $this->innodbBufferPoolPagesFree !== null ? $this->innodbBufferPoolPagesTotal - $this->innodbBufferPoolPagesFree : null;
    }

    public ?int $innodbBufferPoolPageSize {
        get => $this->getInt('Innodb_page_size');
    }

    // total number of logical reads requested from InnoDB (served from the buffer pool when possible).
    public ?int $innodbBufferPoolReadRequests {
        get => $this->getInt('Innodb_buffer_pool_read_requests');
    }

    // number of physical reads from disk because the pages were not cached in the buffer pool.
    public ?int $innodbBufferPoolReadsFromDisk {
        get => $this->getInt('Innodb_buffer_pool_reads');
    }

    public ?int $innodbBufferPoolReadsFromCache {
        get => $this->innodbBufferPoolReadRequests !== null && $this->innodbBufferPoolReadsFromDisk != null
            ? $this->innodbBufferPoolReadRequests - $this->innodbBufferPoolReadsFromDisk
            : null;
    }

    public ?float $innoDbBufferPoolHitRate {
        get => $this->innodbBufferPoolReadsFromCache !== null && $this->innodbBufferPoolReadRequests > 0 ? round($this->innodbBufferPoolReadsFromCache / $this->innodbBufferPoolReadRequests * 100, 2) : null;
    }

    // volume read (bytes)
    public ?int $innodbDataRead {
        get => $this->getInt('Innodb_data_read');
    }

    // number of disk reads
    public ?int $innodbDataReads {
        get => $this->getInt('Innodb_data_reads');
    }

    public ?float $innodbDataReadsPerSec {
        get => $this->getFloat('innodb_data_reads_per_second');
    }

    // number of disk writes
    public ?int $innodbDataWrites {
        get => $this->getInt('Innodb_data_writes');
    }

    public ?float $innodbDataWritesPerSec {
        get => $this->getFloat('innodb_data_writes_per_second');
    }

    // volume written (bytes)
    public ?int $innodbDataWritten {
        get => $this->getInt('Innodb_data_written');
    }

    // number of queries that exceeded the long_query_time threshold (slow queries).
    public ?int $slowQueries {
        get => $this->getInt('Slow_queries');
    }

    // number of table locks acquired immediately, without waiting.
    public ?int $tableLocksImmediate {
        get => $this->getInt('Table_locks_immediate');
    }

    // number of times a query had to wait to acquire a table lock.
    public ?int $tableLocksWaited {
        get => $this->getInt('Table_locks_waited');
    }

    public ?int $tableLocksWaitedDelta {
        get => $this->getInt('table_locks_waited_delta');
    }

    public ?int $tableLocksImmediateDelta {
        get => $this->getInt('table_locks_immediate_delta');
    }

    // computed from a delta
    public ?float $tableLocksWaitedPerSec {
        get => $this->getFloat('table_locks_waited_per_second');
    }

    // the % of times a query had to wait to acquire a table lock.
    // computed from the delta ratio rather than the cumulative counters => more accurate (less smoothed)
    public ?float $tableLocksWaitPercent {
        get => $this->getFloat('table_locks_waited_percent');
    }

    public ?float $tableLockWaitPercentSinceReboot {
        get {
            if ($this->tableLocksWaited === null || $this->tableLocksImmediate <= 0) {
                return null;
            }

            $total = $this->tableLocksWaited + $this->tableLocksImmediate;

            if ($total <= 0) {
                return null;
            }

            return round($this->tableLocksWaited / $total * 100, 2);
        }
    }

    // number of threads created by the server to handle connections (vs reuse from the thread cache).
    public ?int $threadsCreated {
        get => $this->getInt('Threads_created');
    }

    public ?float $threadsCacheMissPercent {
        get => $this->threadsCreated !== null && $this->connections > 0 ? round($this->threadsCreated / $this->connections * 100, 2) : null;
    }

    // equivalent of bufferPoolSize computed from the pages
    public ?int $innoDbPagesTotalBytes {
        get => $this->innodbBufferPoolPagesTotal !== null && $this->innodbBufferPoolPageSize !== null
            ? $this->innodbBufferPoolPagesTotal * $this->innodbBufferPoolPageSize
            : null;
    }

    public ?int $innoDbPagesFreeBytes {
        get => $this->innodbBufferPoolPagesFree !== null && $this->innodbBufferPoolPageSize !== null
            ? $this->innodbBufferPoolPagesFree * $this->innodbBufferPoolPageSize
            : null;
    }

    public ?int $innoDbPagesUsedBytes {
        get => $this->innoDbPagesTotalBytes !== null && $this->innoDbPagesFreeBytes !== null
            ? $this->innoDbPagesTotalBytes - $this->innoDbPagesFreeBytes
            : null;
    }

    /**
     * Pool usage in %
     * Either MariaDB -> innodbBufferPoolBytesFree is used
     * Or MySQL -> the pages are used
     */
    public function getInnoDbBufferPoolUsage(?int $innoDbBufferPoolSize = null): ?float
    {
        if ($this->innodbBufferPoolBytesFree !== null && $innoDbBufferPoolSize > 0) {
            return round(($innoDbBufferPoolSize - $this->innodbBufferPoolBytesFree) / $innoDbBufferPoolSize * 100, 2);
        }

        if ($this->innoDbPagesUsedBytes !== null && $this->innoDbPagesTotalBytes > 0) {
            return round($this->innoDbPagesUsedBytes / $this->innoDbPagesTotalBytes * 100, 2);
        }

        return null;
    }
}
