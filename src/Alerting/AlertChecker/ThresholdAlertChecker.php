<?php

declare(strict_types=1);

namespace App\Alerting\AlertChecker;

use App\Alerting\AlertMetric;
use App\Alerting\Config\ThresholdConfigInterface;
use App\Alerting\Dto\SpottedAlert;
use App\Entity\Alert;
use App\Entity\Enums\AlertType;
use App\Metrics\Dto\Bag;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\Bag\Caddy\CaddyBag;
use App\Metrics\Dto\Bag\FrankenPhp\FrankenPhpBag;
use App\Metrics\Dto\Bag\Mysql\MysqlInfoSchemaBag;
use App\Metrics\Dto\Bag\Mysql\MysqlStatusBag;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\Bag\System\SystemBag;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(autowire: true)]
class ThresholdAlertChecker implements AlertCheckerInterface
{
    public function support(Alert $alert, MetricBagDto $metricBag): bool
    {
        return in_array($alert->getAlertMetric()->getType(), [
            AlertType::MaxValueThreshold,
            AlertType::MinValueThreshold,
            AlertType::MaxPercentThreshold,
            AlertType::MinPercentThreshold,
        ]);
    }

    public function check(Alert $alert, MetricBagDto $metricBag): ?SpottedAlert
    {
        $value = match (true) {
            $metricBag instanceof SystemBag => $this->extractSystemBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof PhpBag => $this->extractPhpBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof ApacheBag => $this->extractApacheBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof NginxBag => $this->extractNginxBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof CaddyBag => $this->extractCaddyBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof FrankenPhpBag => $this->extractFrankenPhpBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof MysqlStatusBag => $this->extractMysqlStatusBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof MysqlInfoSchemaBag => $this->extractMysqlInfoSchemaBagValue($alert->getAlertMetric(), $metricBag),
            $metricBag instanceof RedisBag => $this->extractRedisBagValue($alert->getAlertMetric(), $metricBag),
            default => null,
        };

        if ($value === null) {
            return null;
        }

        /** @var ThresholdConfigInterface&Bag $config */
        $config = $alert->getConfig();

        return $config->isSatisfiedBy($value, $alert->getAlertMetric()->getType())
            ? new SpottedAlert($alert, $value)
            : null;
    }

    private function extractSystemBagValue(AlertMetric $alertMetric, SystemBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::SystemRamUsage => $bag->ram->usedPercent,
            AlertMetric::SystemCpuUsage => $bag->cpu->usedPercent,
            AlertMetric::SystemDiskUsage => $bag->disk->usedPercent,
            default => null,
        };
    }

    private function extractPhpBagValue(AlertMetric $alertMetric, PhpBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::PhpOpcacheMemoryUsagePercent => $bag->opcache->status->memory->usedPercent,
            AlertMetric::PhpOpcacheHitRate => $bag->opcache->status->statistics->opcacheHitRate,
            AlertMetric::PhpApcuHitRate => $bag->apcu->cache->hitRate,
            AlertMetric::PhpFpmMemoryPeakPercent => $bag->fpm->memoryPeakPercent,
            AlertMetric::PhpApcuMemoryUsagePercent => $bag->apcu->sma->usedMemPercent,
            AlertMetric::PhpFpmMemoryPeakValue => $bag->fpm->memoryPeak,
            AlertMetric::PhpFpmReqPerSec => $bag->fpm->reqPerSec,
            AlertMetric::PhpFpmActiveProcesses => $bag->fpm->activeProcesses,
            AlertMetric::PhpFpmMaxChildrenReached => $bag->fpm->maxChildrenReached,
            AlertMetric::PhpFpmIdleProcesses => $bag->fpm->idleProcesses,
            AlertMetric::PhpFpmSlowRequests => $bag->fpm->slowRequests,
            default => null,
        };
    }

    private function extractApacheBagValue(AlertMetric $alertMetric, ApacheBag $bag): ?float
    {
        return match ($alertMetric) {
            AlertMetric::ApacheBusyWorkers => $bag->workers->usedPercent,
            AlertMetric::ApacheBusyProcess => $bag->scoreboard->usedPercent,
            AlertMetric::ApacheReqPerSec => $bag->realRequestsPerSecond,
            AlertMetric::ApacheOutgoingBytes => $bag->realBytesPerSecond,
            default => null,
        };
    }

    private function extractNginxBagValue(AlertMetric $alertMetric, NginxBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::NginxActiveConnectionsPercent => $bag->status->activeConnectionsPercent,
            AlertMetric::NginxActiveConnections => $bag->status->activeConnections,
            AlertMetric::NginxRequestsPerSec => $bag->status->requestsPerSecond,
            AlertMetric::NginxWaitingConnections => $bag->status->waitingConnections,
            AlertMetric::NginxRefusedConnections => $bag->status->refusedRequests,
            default => null,
        };
    }

    private function extractCaddyBagValue(AlertMetric $alertMetric, CaddyBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::CaddyMemoryUsage => $bag->processResidentMemoryBytes,
            AlertMetric::CaddyCpuUsage => $bag->processCpuSecondsTotalPercent,
            AlertMetric::CaddyPhpRequestsPerSec => $bag->requestsPerSec->get('php'),
            AlertMetric::CaddyFileServerRequestsPerSec => $bag->requestsPerSec->get('file_server'),
            AlertMetric::CaddyPhpAvgRequestDuration => $bag->avgRequestDurationMs->get('php'),
            AlertMetric::CaddyFileServerAvgRequestDuration => $bag->avgRequestDurationMs->get('file_server'),
            AlertMetric::CaddyPhpAvgResponseDuration => $bag->avgResponseDurationMs->get('php'),
            AlertMetric::CaddyFileServerAvgResponseDuration => $bag->avgResponseDurationMs->get('file_server'),
            AlertMetric::CaddyPhpRespLower250 => $bag->responseDurationSecondsBucketLe250msPecents?->get('php'),
            AlertMetric::CaddyFileServerRespLower250 => $bag->responseDurationSecondsBucketLe250msPecents?->get('file_server'),
            AlertMetric::CaddyPhpBytesReceivedPerSec => $bag->requestSizePerSec->get('php'),
            AlertMetric::CaddyFileServerBytesReceivedPerSec => $bag->requestSizePerSec->get('file_server'),
            AlertMetric::CaddyPhpBytesSentPerSec => $bag->responseSizePerSec->get('php'),
            AlertMetric::CaddyFileServerBytesSentPerSec => $bag->responseSizePerSec->get('file_server'),
            AlertMetric::CaddyPhpAvgRequestSize => $bag->avgRequestSizeBytes->get('php'),
            AlertMetric::CaddyFileServerAvgRequestSize => $bag->avgRequestSizeBytes->get('file_server'),
            AlertMetric::CaddyPhpAvgResponseSize => $bag->avgResponseSizeBytes->get('php'),
            AlertMetric::CaddyFileServerAvgResponseSize => $bag->avgResponseSizeBytes->get('file_server'),
            default => null,
        };
    }

    private function extractFrankenPhpBagValue(AlertMetric $alertMetric, FrankenPhpBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::FrankenPhpBusyThreadsPercent => $bag->busyThreadsPercent,
            AlertMetric::FrankenPhpBusyThreads => $bag->busyThreads,
            default => null,
        };
    }

    private function extractMysqlStatusBagValue(AlertMetric $alertMetric, MysqlStatusBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::MysqlMaxConnectionsReached => $bag->maxUsedConnections,
            AlertMetric::MysqlQueriesPerSecond => $bag->questionsPerSecond,
            AlertMetric::MysqlSlowQueriesCount => $bag->slowQueries,
            AlertMetric::MysqlInnoDbBufferPoolUsagePercent => $bag->getInnoDbBufferPoolUsage(), // note: the optional buffer pool size parameter is not provided here
            AlertMetric::MysqlInnoDbBufferPoolHitRate => $bag->innoDbBufferPoolHitRate,
            AlertMetric::MysqlThreadsConnected => $bag->threadsConnected,
            AlertMetric::MysqlThreadRunning => $bag->threadsRunning,
            AlertMetric::MysqlTemporaryTablesPercent => $bag->createdTmpDiskTablesPercent,
            default => null,
        };
    }

    private function extractMysqlInfoSchemaBagValue(AlertMetric $alertMetric, MysqlInfoSchemaBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::MysqlDataLength => $bag->dataWeight['data_length'] ?? null,
            default => null,
        };
    }

    private function extractRedisBagValue(AlertMetric $alertMetric, RedisBag $bag): int|float|null
    {
        return match ($alertMetric) {
            AlertMetric::RedisMemoryUsagePercent => $bag->memory->usedPercent,
            AlertMetric::RedisMemoryUsageValue => $bag->memory->used,
            AlertMetric::RedisMemoryPeak => $bag->memory->usedPeak,
            AlertMetric::RedisOperationsPerSecond => $bag->stats->opsPerSec,
            AlertMetric::RedisHitRate => $bag->stats->hitRate,
            AlertMetric::RedisClientsConnected => $bag->clients->connected,
            default => null,
        };
    }
}
