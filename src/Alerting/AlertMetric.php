<?php

declare(strict_types=1);

namespace App\Alerting;

use App\Alerting\Config\AlertConfigInterface;
use App\Alerting\Config\BytesPerSecValueThresholdConfig;
use App\Alerting\Config\BytesValueThresholdConfig;
use App\Alerting\Config\MsValueThresholdConfig;
use App\Alerting\Config\OutdatedVersionConfig;
use App\Alerting\Config\PercentThresholdConfig;
use App\Alerting\Config\NumberThresholdConfig;
use App\Alerting\Config\SymfonyTransportMessagesConfig;
use App\Entity\Enums\AlertType;
use App\Entity\Enums\Component;
use App\Form\Alert\Config\MsThresholdConfigType;
use App\Form\Alert\Config\OutdatedVersionConfigType;
use App\Form\Alert\Config\PercentThresholdConfigType;
use App\Form\Alert\Config\NumberThresholdConfigType;
use App\Form\Alert\Config\SymfonyTransportMessagesConfigType;

enum AlertMetric: string
{
    # System
    case SystemRamUsage = 'system.ram_usage';
    case SystemCpuUsage = 'system.cpu_usage';
    case SystemDiskUsage = 'system.disk_usage';

    # Apache
    case ApacheVersion = 'apache.version';
    case ApacheBusyWorkers = 'apache.busy_workers';
    case ApacheBusyProcess = 'apache.busy_process';
    case ApacheReqPerSec = 'apache.req_per_sec';
    case ApacheOutgoingBytes = 'apache.outgoing_bytes';

    # Nginx
    case NginxVersion = 'nginx.version';
    case NginxActiveConnectionsPercent = 'nginx.active_connections_percent';
    case NginxActiveConnections = 'nginx.active_connections';
    case NginxRequestsPerSec = 'nginx.requests_per_sec';
    case NginxWaitingConnections = 'nginx.waiting_connections';
    case NginxRefusedConnections = 'nginx.refused_connections';

    # Caddy
    case CaddyVersion = 'caddy.version';
    case CaddyMemoryUsage = 'caddy.memory_usage';
    case CaddyCpuUsage = 'caddy.cpu_usage';
    case CaddyPhpRequestsPerSec = 'caddy.php_requests_per_sec';
    case CaddyFileServerRequestsPerSec = 'caddy.file_server_requests_per_sec';
    case CaddyPhpAvgRequestDuration = 'caddy.php_avg_request_duration';
    case CaddyFileServerAvgRequestDuration = 'caddy.file_server_avg_request_duration';
    case CaddyPhpAvgResponseDuration = 'caddy.php_avg_response_duration';
    case CaddyFileServerAvgResponseDuration = 'caddy.file_server_avg_response_duration';
    case CaddyPhpRespLower250 = 'caddy.php_resp_lower_250';
    case CaddyFileServerRespLower250 = 'caddy.file_server_resp_lower_250';
    case CaddyPhpBytesReceivedPerSec = 'caddy.php_bytes_received_per_sec';
    case CaddyFileServerBytesReceivedPerSec = 'caddy.file_server_bytes_received_per_sec';
    case CaddyPhpBytesSentPerSec = 'caddy.php_bytes_sent_per_sec';
    case CaddyFileServerBytesSentPerSec = 'caddy.file_server_bytes_sent_per_sec';
    case CaddyPhpAvgRequestSize = 'caddy.php_avg_request_size';
    case CaddyFileServerAvgRequestSize = 'caddy.file_server_avg_request_size';
    case CaddyPhpAvgResponseSize = 'caddy.php_avg_response_size';
    case CaddyFileServerAvgResponseSize = 'caddy.file_server_avg_response_size';

    # PHP
    case PhpVersion = 'php.version';
    case PhpOpcacheMemoryUsagePercent = 'php.opcache_memory_usage';
    case PhpOpcacheHitRate = 'php.opcache_hit_rate';
    case PhpApcuMemoryUsagePercent = 'php.apcu_memory_usage';
    case PhpApcuHitRate = 'php.apcu_hit_rate';
    case PhpFpmReqPerSec = 'php.fpm_req_per_sec';
    case PhpFpmActiveProcesses = 'php.fpm_active_processes';
    case PhpFpmMemoryPeakPercent = 'php.fpm_memory_peak_percent';
    case PhpFpmMemoryPeakValue = 'php.fpm_memory_peak_value';
    case PhpFpmMaxChildrenReached = 'php.fpm_max_children_reached';
    case PhpFpmIdleProcesses = 'php.fpm_idle_processes';
    case PhpFpmSlowRequests = 'php.fpm_slow_requests';

    # FrankenPHP
    case FrankenPhpBusyThreadsPercent = 'frankenphp.busy_threads_percent';
    case FrankenPhpBusyThreads = 'frankenphp.busy_threads';

    # Symfony
    case SymfonyVersion = 'symfony.version';
    case SymfonyTransportMessages = 'symfony.transport_messages';
    case SymfonyOutdatedFlexRecipes = 'symfony.outdated_flex_recipes';

    # Mysql
    case MysqlVersion = 'mysql.version';
    case MysqlMaxConnectionsReached = 'mysql.max_connections_reached';
    case MysqlQueriesPerSecond = 'mysql.queries_per_sec';
    case MysqlSlowQueriesCount = 'mysql.slow_queries_count';
    case MysqlInnoDbBufferPoolUsagePercent = 'mysql.innodb_buffer_pool_usage_percent';
    case MysqlInnoDbBufferPoolHitRate = 'mysql.innodb_buffer_pool_hit_rate';
    case MysqlThreadsConnected = 'mysql.threads_connected';
    case MysqlThreadRunning = 'mysql.thread_running';
    case MysqlTemporaryTablesPercent = 'mysql.temporary_tables_percent';
    case MysqlDataLength = 'mysql.data_length';

    # Redis
    case RedisVersion = 'redis.version';
    case RedisMemoryUsagePercent = 'redis.memory_usage_percent';
    case RedisMemoryUsageValue = 'redis.memory_usage_value';
    case RedisMemoryPeak = 'redis.memory_peak';
    case RedisOperationsPerSecond = 'redis.operations_per_sec';
    case RedisHitRate = 'redis.hit_rate';
    case RedisClientsConnected = 'redis.clients_connected';

    /**
     * @return iterable<AlertMetric>
     */
    public static function byComponent(Component $component): iterable
    {
        foreach (self::cases() as $case) {
            if ($case->component() === $component) {
                yield $case;
            }
        }
    }

    public function label(): string
    {
        return match ($this) {
            # System
            self::SystemRamUsage => 'RAM usage',
            self::SystemCpuUsage => 'CPU usage',
            self::SystemDiskUsage => 'Disk usage',

            # Apache
            self::ApacheVersion => 'Version',
            self::ApacheBusyWorkers => 'Busy workers',
            self::ApacheBusyProcess => 'Busy process',
            self::ApacheReqPerSec => 'Requests per second',
            self::ApacheOutgoingBytes => 'Outgoing traffic',

            # Nginx
            self::NginxVersion => 'Version',
            self::NginxActiveConnectionsPercent => 'Active connections (%)',
            self::NginxActiveConnections => 'Active connections',
            self::NginxRequestsPerSec => 'Requests per second',
            self::NginxWaitingConnections => 'Waiting connections',
            self::NginxRefusedConnections => 'Refused connections',

            # Caddy
            self::CaddyVersion => 'Version',
            self::CaddyMemoryUsage => 'Memory usage',
            self::CaddyCpuUsage => 'CPU usage',
            self::CaddyPhpRequestsPerSec => '[PHP] requests per second',
            self::CaddyFileServerRequestsPerSec => '[File server] requests per second',
            self::CaddyPhpAvgRequestDuration => '[PHP] avg request duration',
            self::CaddyFileServerAvgRequestDuration => '[File server] avg request duration',
            self::CaddyPhpAvgResponseDuration => '[PHP] avg response duration',
            self::CaddyFileServerAvgResponseDuration => '[File server] avg response duration',
            self::CaddyPhpRespLower250 => '[PHP] resp < 250ms',
            self::CaddyFileServerRespLower250 => '[File server] resp < 250ms',
            self::CaddyPhpBytesReceivedPerSec => '[PHP] bytes received per second',
            self::CaddyFileServerBytesReceivedPerSec => '[File server] bytes received per second',
            self::CaddyPhpBytesSentPerSec => '[PHP] bytes sent per second',
            self::CaddyFileServerBytesSentPerSec => '[File server] bytes sent per second',
            self::CaddyPhpAvgRequestSize => '[PHP] avg request size',
            self::CaddyFileServerAvgRequestSize => '[File server] avg request size',
            self::CaddyPhpAvgResponseSize => '[PHP] avg response size',
            self::CaddyFileServerAvgResponseSize => '[File server] avg response size',

            # PHP
            self::PhpVersion => 'Version',
            self::PhpOpcacheMemoryUsagePercent => '[OPcache] memory usage',
            self::PhpOpcacheHitRate => '[OPcache] hit rate',
            self::PhpApcuMemoryUsagePercent => '[APCu] memory usage',
            self::PhpApcuHitRate => '[APCu] hit rate',
            self::PhpFpmReqPerSec => '[FPM] Requests per second',
            self::PhpFpmActiveProcesses => '[FPM] Active processes',
            self::PhpFpmMemoryPeakPercent => '[FPM] Memory peak percent',
            self::PhpFpmMemoryPeakValue => '[FPM] Memory peak value',
            self::PhpFpmMaxChildrenReached => '[FPM] Max children reached',
            self::PhpFpmIdleProcesses => '[FPM] Idle processes',
            self::PhpFpmSlowRequests => '[FPM] Slow requests',

            # FrankenPHP
            self::FrankenPhpBusyThreadsPercent => 'Busy threads (%)',
            self::FrankenPhpBusyThreads => 'Busy threads',

            # Symfony
            self::SymfonyVersion => 'Version',
            self::SymfonyTransportMessages => 'Messages in transport',
            self::SymfonyOutdatedFlexRecipes => 'Outdated Flex recipes',

            # Mysql
            self::MysqlVersion => 'Version',
            self::MysqlMaxConnectionsReached => 'Max connections reached',
            self::MysqlQueriesPerSecond => 'Queries per second',
            self::MysqlSlowQueriesCount => 'Slow queries',
            self::MysqlInnoDbBufferPoolUsagePercent => '[InnoDb] Buffer pool usage',
            self::MysqlInnoDbBufferPoolHitRate => '[InnoDb] Buffer pool hit rate',
            self::MysqlThreadsConnected => 'Threads connected',
            self::MysqlThreadRunning => 'Thread running',
            self::MysqlTemporaryTablesPercent => 'Temporary tables usage',
            self::MysqlDataLength => 'Data length',

            # Redis
            self::RedisVersion => 'Version',
            self::RedisMemoryUsagePercent => 'Memory usage (%)',
            self::RedisMemoryUsageValue => 'Memory usage',
            self::RedisMemoryPeak => 'Memory peak',
            self::RedisOperationsPerSecond => 'Operations per second',
            self::RedisHitRate => 'Hit rate',
            self::RedisClientsConnected => 'Clients connected',
        };
    }

    public function unit(): ?Unit
    {
        return match ($this) {
            self::SystemCpuUsage,
            self::SystemDiskUsage,
            self::SystemRamUsage,
            self::NginxActiveConnectionsPercent,
            self::CaddyCpuUsage,
            self::CaddyPhpRespLower250,
            self::CaddyFileServerRespLower250,
            self::PhpOpcacheMemoryUsagePercent,
            self::PhpOpcacheHitRate,
            self::PhpApcuHitRate,
            self::PhpApcuMemoryUsagePercent,
            self::FrankenPhpBusyThreadsPercent,
            self::MysqlInnoDbBufferPoolUsagePercent,
            self::MysqlInnoDbBufferPoolHitRate,
            self::MysqlTemporaryTablesPercent,
            self::RedisMemoryUsagePercent,
            self::RedisHitRate => Unit::Percent,
            self::ApacheOutgoingBytes,
            self::CaddyPhpBytesReceivedPerSec,
            self::CaddyFileServerBytesReceivedPerSec,
            self::CaddyPhpBytesSentPerSec,
            self::CaddyFileServerBytesSentPerSec => Unit::BytePerSec,
            self::PhpFpmMemoryPeakValue,
            self::CaddyMemoryUsage,
            self::CaddyPhpAvgRequestSize,
            self::CaddyFileServerAvgRequestSize,
            self::CaddyPhpAvgResponseSize,
            self::CaddyFileServerAvgResponseSize,
            self::MysqlDataLength,
            self::RedisMemoryUsageValue,
            self::RedisMemoryPeak => Unit::Byte,
            self::CaddyPhpAvgRequestDuration,
            self::CaddyFileServerAvgRequestDuration,
            self::CaddyPhpAvgResponseDuration,
            self::CaddyFileServerAvgResponseDuration => Unit::Millisecond,
            default => null,
        };
    }

    public function getType(): AlertType
    {
        return match ($this) {
            self::SystemRamUsage,
            self::ApacheBusyWorkers,
            self::ApacheBusyProcess,
            self::SystemCpuUsage,
            self::PhpOpcacheMemoryUsagePercent,
            self::PhpFpmMemoryPeakPercent,
            self::PhpApcuMemoryUsagePercent,
            self::SystemDiskUsage,
            self::NginxActiveConnectionsPercent,
            self::FrankenPhpBusyThreadsPercent,
            self::MysqlInnoDbBufferPoolUsagePercent,
            self::MysqlTemporaryTablesPercent,
            self::RedisMemoryUsagePercent,
            self::CaddyCpuUsage => AlertType::MaxPercentThreshold,

            self::NginxWaitingConnections,
            self::PhpFpmIdleProcesses => AlertType::MinValueThreshold,

            self::CaddyPhpRespLower250,
            self::CaddyFileServerRespLower250,
            self::PhpApcuHitRate,
            self::MysqlInnoDbBufferPoolHitRate,
            self::PhpOpcacheHitRate,
            self::RedisHitRate => AlertType::MinPercentThreshold,

            self::PhpVersion,
            self::ApacheVersion,
            self::NginxVersion,
            self::CaddyVersion,
            self::SymfonyVersion,
            self::MysqlVersion,
            self::RedisVersion => AlertType::Version,

            self::SymfonyTransportMessages,
            self::SymfonyOutdatedFlexRecipes => AlertType::Custom,

            // the most common case
            default => AlertType::MaxValueThreshold,
        };
    }

    /**
     * @return class-string<AlertConfigInterface>|null
     */
    public function configBagClass(): ?string
    {
        // specific
        $class = match ($this) {
            self::SymfonyTransportMessages => SymfonyTransportMessagesConfig::class,
            default => null,
        };

        if ($class) {
            return $class;
        }

        // then by unit
        $class = match ($this->unit()) {
            Unit::Byte => BytesValueThresholdConfig::class,
            Unit::BytePerSec => BytesPerSecValueThresholdConfig::class,
            Unit::Millisecond => MsValueThresholdConfig::class,
            default => null,
        };

        if ($class) {
            return $class;
        }

        // then by type
        return match ($this->getType()) {
            AlertType::MinPercentThreshold,
            AlertType::MaxPercentThreshold => PercentThresholdConfig::class,
            AlertType::MinValueThreshold,
            AlertType::MaxValueThreshold => NumberThresholdConfig::class,
            AlertType::Version => OutdatedVersionConfig::class,
            default => null,
        };
    }

    public function configFormTypeClass(): ?string
    {
        return match ($this->configBagClass()) {
            BytesValueThresholdConfig::class,
            BytesPerSecValueThresholdConfig::class,
            NumberThresholdConfig::class => NumberThresholdConfigType::class,
            PercentThresholdConfig::class => PercentThresholdConfigType::class,
            MsValueThresholdConfig::class => MsThresholdConfigType::class,
            SymfonyTransportMessagesConfig::class => SymfonyTransportMessagesConfigType::class,
            OutdatedVersionConfig::class => OutdatedVersionConfigType::class,
            default => null,
        };
    }

    public function component(): Component
    {
        return match (true) {
            str_starts_with($this->value, 'system.') => Component::System,
            str_starts_with($this->value, 'redis.') => Component::Redis,
            str_starts_with($this->value, 'mysql.') => Component::MySQL,
            str_starts_with($this->value, 'apache.') => Component::Apache,
            str_starts_with($this->value, 'php.') => Component::PHP,
            str_starts_with($this->value, 'symfony.') => Component::Symfony,
            str_starts_with($this->value, 'nginx.') => Component::Nginx,
            str_starts_with($this->value, 'caddy.') => Component::Caddy,
            str_starts_with($this->value, 'frankenphp.') => Component::FrankenPHP,
            default => throw new \Exception('Unknown component for metric: ' . $this->value),
        };
    }
}
