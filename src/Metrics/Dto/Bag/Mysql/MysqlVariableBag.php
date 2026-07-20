<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Mysql\MysqlFamily;

class MysqlVariableBag extends MetricBagDto
{
    public ?string $characterSetClient {
        get => $this->get('character_set_client');
    }

    public ?string $characterSetConnection {
        get => $this->get('character_set_connection');
    }

    public ?string $characterSetDatabase {
        get => $this->get('character_set_database');
    }

    public ?string $characterSetResults {
        get => $this->get('character_set_results');
    }

    public ?string $characterSetServer {
        get => $this->get('character_set_server');
    }

    public ?string $characterSetSystem {
        get => $this->get('character_set_system');
    }

    public ?string $collationConnection {
        get => $this->get('collation_connection');
    }

    public ?string $collationServer {
        get => $this->get('collation_server');
    }

    public ?int $innodbBufferPoolSize {
        get => $this->getInt('innodb_buffer_pool_size');
    }

    public ?float $joinBufferSize {
        get => $this->getInt('join_buffer_size');
    }

    public ?float $longQueryTime {
        get => $this->getFloat('long_query_time');
    }

    public ?int $maxConnections {
        get => $this->getInt('max_connections');
    }

    public ?int $maxHeapTableSize {
        get => $this->getInt('max_heap_table_size');
    }

    public ?string $slowQueryLog {
        get => $this->get('slow_query_log');
    }

    public ?bool $slowQueryLogEnabled {
        get => is_string($this->slowQueryLog) ? in_array($this->slowQueryLog, ['ON', 'on', '1']) : null;
    }

    public ?string $slowQueryLogFile {
        get => $this->get('slow_query_log_file');
    }

    public ?int $sortBufferSize {
        get => $this->getInt('sort_buffer_size');
    }

    public ?string $systemTimeZone {
        get => $this->get('system_time_zone');
    }

    public ?int $tableOpenCache {
        get => $this->getInt('table_open_cache');
    }

    public ?int $threadCacheSize {
        get => $this->getInt('thread_cache_size');
    }

    public ?string $timeZone {
        get => $this->get('time_zone');
    }

    public ?int $timestamp {
        get => $this->getInt('timestamp');
    }

    public private(set) ?\DateTimeImmutable $dateTime {
        get => $this->dateTime ??= ($this->timestamp ? new \DateTimeImmutable('@' . $this->timestamp) : null);
    }

    public ?int $tmpTableSize {
        get => $this->getInt('tmp_table_size');
    }

    public ?string $mysqlVersion {
        get => $this->get('version');
    }

    public ?MysqlFamily $family {
        get => $this->family ??= MysqlFamily::tryFromVersionComment($this->versionComment);
    }

    public ?string $versionComment {
        get => $this->get('version_comment');
    }

    public ?int $waitTimeout {
        get => $this->getInt('wait_timeout');
    }

    public ?string $logBin {
        get => $this->get('log_bin');
    }

    public ?bool $binLogEnabled {
        get => is_string($this->logBin) ? mb_strtolower($this->logBin) === 'on' : null;
    }
}
