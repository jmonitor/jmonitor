<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Entity\Enums\Component;

enum Metric: string
{
    case SystemRamUsage = 'system.ram_usage';
    case SystemCpuUsage = 'system.cpu_usage';
    case SystemDiskUsage = 'system.disk_usage';
    case SystemInformations = 'system.informations';
    case ApacheVersion = 'apache.version';
    case ApacheMpm = 'apache.mpm';
    case ApacheUptime = 'apache.uptime';
    case ApacheLoadAverage = 'apache.load_average';
    case ApacheBusyWorkers = 'apache.busy_workers';
    case ApacheBusyProcess = 'apache.busy_process';
    case ApacheReqPerSec = 'apache.req_per_sec';
    case ApacheBytesPerSec = 'apache.bytes_per_sec';
    case ApacheInformations = 'apache.informations';
    case PostgresVersion = 'postgres.version';
    case PostgresCapacity = 'postgres.capacity';
    case PostgresConfig = 'postgres.config';
    case PostgresTuning = 'postgres.tuning';
    case PostgresConnectionsUsage = 'postgres.connections_usage';
    case PostgresConnectionsStates = 'postgres.connections_states';
    case PostgresTransactionsPerSec = 'postgres.transactions_per_sec';
    case PostgresRollbackRatio = 'postgres.rollback_ratio';
    case PostgresCacheHitRatio = 'postgres.cache_hit_ratio';
    case PostgresDiskReadsPerSec = 'postgres.disk_reads_per_sec';
    case PostgresTempFiles = 'postgres.temp_files';
    case PostgresRowsWrittenPerSec = 'postgres.rows_written_per_sec';
    case PostgresSlowQueriesTop = 'postgres.slow_queries_top';
    case PostgresDeadTupleRatio = 'postgres.dead_tuple_ratio';
    case PostgresIndexUsageRatio = 'postgres.index_usage_ratio';
    case PostgresScansPerSec = 'postgres.scans_per_sec';
    case PostgresDeadlocksPerSec = 'postgres.deadlocks_per_sec';
    case PostgresBlockedSessions = 'postgres.blocked_sessions';
    case PostgresOldestTransaction = 'postgres.oldest_transaction';
    case PostgresIdleInTransaction = 'postgres.idle_in_transaction';
    case PostgresBlockingChain = 'postgres.blocking_chain';
    case PostgresDatabaseSize = 'postgres.database_size';
    case PostgresStorageBreakdown = 'postgres.storage_breakdown';
    case MysqlVersion = 'mysql.version';
    case MysqlConnectionsUsage = 'mysql.connections_usage';
    case MysqlMaxConnectionsReached = 'mysql.max_connections_reached';
    case MysqlQueriesPerSecond = 'mysql.queries_per_sec';
    case MysqlThreadsConnected = 'mysql.threads_connected';
    case MysqlSlowQueriesCount = 'mysql.slow_queries_count';
    case MysqlSlowQueriesTop = 'mysql.slow_queries_top';
    case MysqlThreadsRunning = 'mysql.threads_running';
    case MysqlInformations = 'mysql.informations';
    case MysqlServerQueriesByType = 'mysql.server_queries_by_type';
    case MysqlConfig = 'mysql.config';
    case MysqlInnodbBufferPoolUsage = 'mysql.innodb_buffer_pool_usage';
    case MysqlInnodbBufferPoolHitRate = 'mysql.innodb_buffer_pool_hit_rate';
    case MysqlInnodbRamRatio = 'mysql.innodb_ram_ratio';
    case MysqlInnodbReadsPerSec = 'mysql.innodb_reads_per_sec';
    case MysqlInnodbWritesPerSec = 'mysql.innodb_writes_per_sec';
    case MysqlTmpTables = 'mysql.tmp_tables';
    case MysqlTablesLockWaitsPerSec = 'mysql.tables_lock_waits_per_sec';
    case MysqlTablesLockWaitsPercent = 'mysql.tables_lock_waits_percent';
    case MysqlThreadCacheMiss = 'mysql.thread_cache_miss';
    case MysqlDataWeight = 'mysql.data_weight';
    case MysqlDataWeightDetail = 'mysql.data_weight_detail';
    case PhpBaseConfig = 'php.base_config';
    case PhpIniExt = 'php.ini_ext';
    case PhpOpcacheMemory = 'php.opcache_memory';
    case PhpSapi = 'php.sapi';
    case PhpVersion = 'php.version';
    case PhpOpcacheHitRate = 'php.opcache_hit_rate';
    case PhpOpcacheConfig = 'php.opcache_config';
    case PhpFpmReqPerSec = 'php.fpm_req_per_sec';
    case PhpFpmActiveProcesses = 'php.fpm_active_processes';
    case PhpFpmConfig = 'php.fpm_config';
    case PhpFpmMemoryPeakPercent = 'php.fpm_memory_peak_percent';
    case PhpFpmMemoryPeakValue = 'php.fpm_memory_peak_value';
    case PhpFpmMaxChildrenReached = 'php.fpm_max_children_reached';
    case PhpFpmSlowRequest = 'php.fpm_slow_request';
    case PhpFpmIdleProcesses = 'php.fpm_idle_processes';
    case PhpApcuMemoryUsage = 'php.apcu_memory_usage';
    case PhpApcuHitRate = 'php.apcu_hit_rate';
    case PhpApcuConfig = 'php.apcu_config';
    case SymfonyVersion = 'symfony.version';
    case SymfonyEnvironment = 'symfony.environment';
    case SymfonyConfigBundles = 'symfony.config_bundles';
    case SymfonyMessenger = 'symfony.messenger';
    case SymfonyFlex = 'symfony.flex';
    case SymfonySchedulerNextTask = 'symfony.scheduler_next_task';
    case SymfonySchedulerTasks = 'symfony.scheduler_tasks';
    case RedisVersion = 'redis.version';
    case RedisMode = 'redis.mode';
    case RedisInformations = 'redis.informations';
    case RedisRequestsPerSecond = 'redis.requests_per_second';
    case RedisMemoryUsage = 'redis.memory_usage';
    case RedisMemoryPeak = 'redis.memory_peak';
    case RedisMaxMemoryPolicy = 'redis.max_memory_policy';
    case RedisPersistence = 'redis.persistence';
    case RedisHitRate = 'redis.hit_rate';
    case RedisStats = 'redis.stats';
    case RedisDbKeys = 'redis.db_keys';
    case RedisDbExpiringKeys = 'redis.db_expiring_keys';
    case RedisDbAvgTtl = 'redis.db_avg_ttl';
    case NginxVersion = 'nginx.version';
    case NginxReqPerSec = 'nginx.req_per_sec';
    case NginxActiveConnections = 'nginx.active_connections';
    case NginxActiveConnectionsRate = 'nginx.active_connections_rate';
    case NginxWaitingConnections = 'nginx.waiting_connections';
    case NginxConnectionsRepartition = 'nginx.connections_repartition';
    case NginxRefusedConnections = 'nginx.refused_connections';
    case NginxCoreConfig = 'nginx.core_config';
    case NginxOtherConfig = 'nginx.other_config';
    case NginxReusedConnectionsRatio = 'nginx.reused_connections_ratio';
    case NginxProcessConfig = 'nginx.processes_config';
    case CaddyVersion = 'caddy.version';
    case CaddyUptime = 'caddy.uptime';
    case CaddyReqPerSec = 'caddy.req_per_sec';
    case CaddyMemoryUsage = 'caddy.memory_usage';
    case CaddyCpuUsage = 'caddy.cpu_usage';
    case CaddyAvgRequestDuration = 'caddy.avg_request_duration';
    case CaddyAvgResponseDuration = 'caddy.avg_response_duration';
    case CaddyRequestsUnder250 = 'caddy.requests_under_250';
    case CaddyRequestSize = 'caddy.request_size';
    case CaddyResponseSize = 'caddy.response_size';
    case CaddyAvgRequestSize = 'caddy.avg_request_size';
    case CaddyAvgResponseSize = 'caddy.avg_response_size';
    case FrankenPhpVersion = 'frankenphp.version';
    case FrankenPhpMode = 'frankenphp.mode';
    case FrankenPhpBusyThreads = 'frankenphp.busy_threads';
    case FrankenPhpBusyThreadsPercent = 'frankenphp.busy_threads_percent';
    case FrankenPhpWorkerBusyWorkersRate = 'frankenphp.worker_busy_workers_rate';
    case FrankenPhpWorkerReadyWorkers = 'frankenphp.worker_ready_workers';
    case FrankenPhpWorkerWorkerRestarts = 'frankenphp.worker_worker_restarts';
    case FrankenPhpWorkerRequestPerSec = 'frankenphp.worker_request_per_sec';
    case FrankenPhpWorkerPhpExecTime = 'frankenphp.worker_php_exec_time';

    public function defaultRenderer(): Renderer
    {
        return match ($this) {
            self::SystemRamUsage => Renderer::Gauge,
            self::SystemCpuUsage => Renderer::Gauge,
            self::SystemDiskUsage => Renderer::Gauge,
            self::ApacheBusyWorkers => Renderer::Gauge,
            self::ApacheBusyProcess => Renderer::Gauge,
            self::ApacheReqPerSec => Renderer::Line,
            self::ApacheBytesPerSec => Renderer::Line,
            self::MysqlConnectionsUsage => Renderer::Gauge,
            self::MysqlQueriesPerSecond => Renderer::Line,
            self::MysqlThreadsConnected => Renderer::Line,
            self::MysqlSlowQueriesCount => Renderer::Line,
            self::MysqlThreadsRunning => Renderer::Line,
            self::MysqlServerQueriesByType => Renderer::Line,
            self::MysqlInnodbBufferPoolUsage => Renderer::Gauge,
            self::MysqlInnodbBufferPoolHitRate => Renderer::Gauge,
            self::MysqlInnodbRamRatio => Renderer::Gauge,
            self::MysqlInnodbReadsPerSec => Renderer::Line,
            self::MysqlInnodbWritesPerSec => Renderer::Line,
            self::MysqlTmpTables => Renderer::Gauge,
            self::MysqlTablesLockWaitsPerSec => Renderer::Line,
            self::MysqlTablesLockWaitsPercent => Renderer::Gauge,
            self::MysqlThreadCacheMiss => Renderer::Gauge,
            self::MysqlDataWeight => Renderer::Line,
            self::PhpOpcacheMemory => Renderer::Gauge,
            self::PhpOpcacheHitRate => Renderer::Gauge,
            self::PhpFpmReqPerSec => Renderer::Line,
            self::PhpFpmActiveProcesses => Renderer::Gauge,
            self::PhpFpmMemoryPeakPercent => Renderer::Gauge,
            self::PhpFpmMemoryPeakValue => Renderer::ConsumerValue,
            self::PhpFpmMaxChildrenReached => Renderer::ConsumerValue,
            self::PhpFpmSlowRequest => Renderer::Line,
            self::PhpFpmIdleProcesses => Renderer::ConsumerValue,
            self::PhpApcuMemoryUsage => Renderer::Gauge,
            self::PhpApcuHitRate => Renderer::Gauge,
            self::SymfonyEnvironment => Renderer::ConsumerValue,
            self::RedisRequestsPerSecond => Renderer::Line,
            self::RedisMemoryUsage => Renderer::Gauge,
            self::RedisHitRate => Renderer::Gauge,
            self::RedisDbKeys => Renderer::Line,
            self::NginxReqPerSec => Renderer::Line,
            self::NginxActiveConnections => Renderer::Gauge,
            self::NginxActiveConnectionsRate => Renderer::Line,
            self::NginxWaitingConnections => Renderer::Line,
            self::CaddyReqPerSec => Renderer::Line,
            self::CaddyMemoryUsage => Renderer::Line,
            self::CaddyCpuUsage => Renderer::Line,
            self::CaddyAvgRequestDuration => Renderer::Line,
            self::CaddyAvgResponseDuration => Renderer::Line,
            self::CaddyRequestsUnder250 => Renderer::Gauge,
            self::CaddyResponseSize => Renderer::Line,
            self::CaddyRequestSize => Renderer::Line,
            self::FrankenPhpBusyThreads => Renderer::Line,
            self::FrankenPhpBusyThreadsPercent => Renderer::Gauge,
            self::FrankenPhpWorkerBusyWorkersRate => Renderer::Gauge,
            self::FrankenPhpWorkerRequestPerSec => Renderer::Line,
            self::FrankenPhpWorkerPhpExecTime => Renderer::Line,
            self::PostgresConnectionsUsage => Renderer::Gauge,
            self::PostgresRollbackRatio => Renderer::Gauge,
            self::PostgresCacheHitRatio => Renderer::Gauge,
            self::PostgresDeadTupleRatio => Renderer::Gauge,
            self::PostgresIndexUsageRatio => Renderer::Gauge,
            self::PostgresConnectionsStates => Renderer::Line,
            self::PostgresTransactionsPerSec => Renderer::Line,
            self::PostgresDiskReadsPerSec => Renderer::Line,
            self::PostgresTempFiles => Renderer::Line,
            self::PostgresRowsWrittenPerSec => Renderer::Line,
            self::PostgresScansPerSec => Renderer::Line,
            self::PostgresDeadlocksPerSec => Renderer::Line,
            self::PostgresDatabaseSize => Renderer::Line,
            default => Renderer::Basic,
        };
    }

    public function availableRenderers(): array
    {
        return match ($this) {
            self::SystemRamUsage => [Renderer::Gauge, Renderer::Line],
            self::SystemCpuUsage => [Renderer::Gauge, Renderer::Line],
            self::SystemDiskUsage => [Renderer::Gauge, Renderer::Line],
            self::PhpFpmReqPerSec => [Renderer::Line], // TODO also offer the consumer-value renderer
            self::ApacheLoadAverage => [Renderer::Basic], // TODO also offer Line
            self::ApacheReqPerSec => [Renderer::Line], // TODO also offer Basic
            self::ApacheBytesPerSec => [Renderer::Line],
            self::MysqlSlowQueriesCount => [Renderer::Bar, Renderer::Basic],
            self::RedisMemoryUsage => [Renderer::Gauge, Renderer::Line, Renderer::Basic],
            self::NginxActiveConnections => [Renderer::Gauge, Renderer::Line],
            self::FrankenPhpBusyThreadsPercent => [Renderer::Gauge, Renderer::Line],
            default => [$this->defaultRenderer()],
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SystemRamUsage => 'RAM usage',
            self::SystemCpuUsage => 'CPU usage',
            self::SystemDiskUsage => 'Disk usage',
            self::SystemInformations => 'System informations',
            self::ApacheVersion => 'Version',
            self::ApacheMpm => 'Server MPM',
            self::ApacheUptime => 'Uptime',
            self::ApacheLoadAverage => 'Load average',
            self::ApacheBusyWorkers => 'Busy workers',
            self::ApacheBusyProcess => 'Busy process',
            self::ApacheReqPerSec => 'Requests per second',
            self::ApacheBytesPerSec => 'Outgoing traffic',
            self::ApacheInformations => 'Apache informations',
            self::PostgresVersion => 'Version',
            self::PostgresCapacity => 'Shared buffers',
            self::PostgresConfig => 'Config: Memory & WAL',
            self::PostgresTuning => 'Config: Planner & maintenance',
            self::PostgresConnectionsUsage => 'Connections usage',
            self::PostgresConnectionsStates => 'Connections by state',
            self::PostgresTransactionsPerSec => 'Transactions per second',
            self::PostgresRollbackRatio => 'Rollback ratio',
            self::PostgresCacheHitRatio => 'Cache hit ratio',
            self::PostgresDiskReadsPerSec => 'Disk blocks read',
            self::PostgresTempFiles => 'Temp files',
            self::PostgresRowsWrittenPerSec => 'Rows written',
            self::PostgresSlowQueriesTop => 'Top slow queries',
            self::PostgresDeadTupleRatio => 'Dead rows ratio',
            self::PostgresIndexUsageRatio => 'Index usage',
            self::PostgresScansPerSec => 'Scans per second',
            self::PostgresDeadlocksPerSec => 'Deadlocks per second',
            self::PostgresBlockedSessions => 'Blocked sessions',
            self::PostgresOldestTransaction => 'Oldest transaction',
            self::PostgresIdleInTransaction => 'Idle in transaction',
            self::PostgresBlockingChain => 'Blocking chain',
            self::PostgresDatabaseSize => 'Database size',
            self::PostgresStorageBreakdown => 'Data vs indexes',
            self::MysqlVersion => 'Version',
            self::MysqlConnectionsUsage => 'Connection usage',
            self::MysqlMaxConnectionsReached => 'Max connections reached',
            self::MysqlQueriesPerSecond => 'Queries per second',
            self::MysqlThreadsConnected => 'Threads connected',
            self::MysqlSlowQueriesCount => 'Slow queries',
            self::MysqlSlowQueriesTop => 'Top slow queries',
            self::MysqlThreadsRunning => 'Threads running',
            self::MysqlInformations => 'MySQL informations',
            self::MysqlServerQueriesByType => 'Server queries',
            self::MysqlConfig => 'Config',
            self::MysqlInnodbBufferPoolUsage => 'Buffer Pool usage',
            self::MysqlInnodbBufferPoolHitRate => 'Buffer Pool Hit rate',
            self::MysqlInnodbRamRatio => 'RAM ratio',
            self::MysqlInnodbReadsPerSec => 'Reads',
            self::MysqlInnodbWritesPerSec => 'Writes',
            self::MysqlTmpTables => 'Temporary tables',
            self::MysqlTablesLockWaitsPerSec => 'Table Lock Waits',
            self::MysqlTablesLockWaitsPercent => 'Table Lock Waits',
            self::MysqlThreadCacheMiss => 'Thread Cache Miss',
            self::MysqlDataWeight => 'Data Length',
            self::MysqlDataWeightDetail => 'Data Length',
            self::PhpBaseConfig => 'Config',
            self::PhpIniExt => null,
            self::PhpOpcacheMemory => 'Memory usage',
            self::PhpSapi => 'Execution mode (SAPI)',
            self::PhpVersion => 'PHP version',
            self::PhpOpcacheHitRate => 'Hit rate',
            self::PhpOpcacheConfig => 'Config',
            self::PhpFpmReqPerSec => 'Requests per second',
            self::PhpFpmActiveProcesses => 'Active processes',
            self::PhpFpmConfig => 'FPM informations',
            self::PhpFpmMemoryPeakPercent => 'Memory peak',
            self::PhpFpmMemoryPeakValue => 'Memory peak',
            self::PhpFpmMaxChildrenReached => 'Max children reached',
            self::PhpFpmSlowRequest => 'Slow requests',
            self::PhpFpmIdleProcesses => 'Idle processes',
            self::PhpApcuMemoryUsage => 'Memory usage',
            self::PhpApcuConfig => 'Config',
            self::PhpApcuHitRate => 'Hit rate',
            self::SymfonyVersion => 'Symfony version',
            self::SymfonyEnvironment => 'Environment',
            self::SymfonyConfigBundles => null,
            self::SymfonyMessenger => 'Messenger',
            self::SymfonyFlex => 'Flex recipes',
            self::SymfonySchedulerNextTask => 'Next task',
            self::SymfonySchedulerTasks => 'Upcoming tasks',
            self::RedisVersion => 'version',
            self::RedisMode => 'Execution mode',
            self::RedisInformations => 'Redis informations',
            self::RedisRequestsPerSecond => 'Operations per second',
            self::RedisMemoryUsage => 'Memory usage',
            self::RedisMemoryPeak => 'Memory peak',
            self::RedisMaxMemoryPolicy => 'Max memory policy',
            self::RedisPersistence => 'Persistence',
            self::RedisHitRate => 'Hit rate',
            self::RedisStats => 'Stats',
            self::RedisDbKeys => 'Keys',
            self::RedisDbExpiringKeys => 'Expiring keys',
            self::RedisDbAvgTtl => 'Average TTL',
            self::NginxVersion => 'Version',
            self::NginxReqPerSec => 'Requests per second',
            self::NginxActiveConnections => 'Active connections',
            self::NginxActiveConnectionsRate => 'Active connections rate',
            self::NginxWaitingConnections => 'Waiting connections',
            self::NginxConnectionsRepartition => 'Distribution',
            self::NginxRefusedConnections => 'Refused connections',
            self::NginxCoreConfig => 'Config: Core & System',
            self::NginxOtherConfig => 'Config: Perf & Security',
            self::NginxReusedConnectionsRatio => 'Reuse Ratio',
            self::NginxProcessConfig => 'Max connections',
            self::CaddyVersion => 'Version',
            self::CaddyUptime => 'Uptime',
            self::CaddyReqPerSec => 'Requests per second',
            self::CaddyMemoryUsage => 'Memory usage',
            self::CaddyCpuUsage => 'CPU usage',
            self::CaddyAvgRequestDuration => 'Avg request duration',
            self::CaddyAvgResponseDuration => 'Avg response duration',
            self::CaddyRequestsUnder250 => 'resp < 250ms',
            self::CaddyRequestSize => 'Bytes received',
            self::CaddyResponseSize => 'Bytes sent',
            self::CaddyAvgRequestSize => 'Avg request size',
            self::CaddyAvgResponseSize => 'Avg response size',
            self::FrankenPhpVersion => 'Version',
            self::FrankenPhpMode => 'Mode',
            self::FrankenPhpBusyThreads => 'Busy threads',
            self::FrankenPhpBusyThreadsPercent => 'Busy threads rate',
            self::FrankenPhpWorkerBusyWorkersRate => 'Busy workers',
            self::FrankenPhpWorkerReadyWorkers => 'Ready workers',
            self::FrankenPhpWorkerWorkerRestarts => 'Worker restarts',
            self::FrankenPhpWorkerRequestPerSec => 'Requests per second',
            self::FrankenPhpWorkerPhpExecTime => 'PHP avg execution time',
        };
    }

    public function component(): Component
    {
        return match (true) {
            str_starts_with($this->value, 'system.') => Component::System,
            str_starts_with($this->value, 'redis.') => Component::Redis,
            str_starts_with($this->value, 'mysql.') => Component::MySQL,
            str_starts_with($this->value, 'postgres.') => Component::Postgres,
            str_starts_with($this->value, 'apache.') => Component::Apache,
            str_starts_with($this->value, 'php.') => Component::PHP,
            str_starts_with($this->value, 'symfony.') => Component::Symfony,
            str_starts_with($this->value, 'nginx.') => Component::Nginx,
            str_starts_with($this->value, 'caddy.') => Component::Caddy,
            str_starts_with($this->value, 'frankenphp.') => Component::FrankenPHP,
            default => throw new \Exception('Unknown component for metric: ' . $this->value),
        };
    }

    public function hasHelp(): bool
    {
        return match ($this) {
            self::SystemInformations,
            self::ApacheUptime,
            self::CaddyUptime => false,
            default => true,
        };
    }
}
