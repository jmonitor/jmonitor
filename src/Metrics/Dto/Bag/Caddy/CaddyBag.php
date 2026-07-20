<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Caddy;

use App\Metrics\Dto\MetricBagDto;

class CaddyBag extends MetricBagDto
{
    public ?string $caddyVersion {
        get => $this->get('version');
    }

    public HandlerMetricBag $requestsTotal {
        get => $this->requestsTotal ??= new HandlerMetricBag($this->all('requests_total'));
    }

    /**
     * Computed
     */
    public HandlerMetricBag $requestsTotalDelta {
        get => $this->requestsTotalDelta ??= new HandlerMetricBag($this->all('requests_total_delta'));
    }

    /**
     * Computed from the delta of requests_total
     */
    public HandlerMetricBag $requestsPerSec {
        get => $this->requestsPerSec ??= new HandlerMetricBag($this->all('requests_per_second'));
    }

    public HandlerMetricBag $requestsInFlight {
        get => $this->requestsInFlight ??= new HandlerMetricBag($this->all('requests_in_flight'));
    }

    public HandlerMetricBag $responseDurationSecondsSum {
        get => $this->responseDurationSecondsSum ??= new HandlerMetricBag($this->all('response_duration_seconds_sum'));
    }

    /**
     * Computed from the deltas of responseDurationSecondsSum and requestsTotal
     */
    public HandlerMetricBag $avgResponseDurationMs {
        get => $this->avgResponseDurationMs ??= new HandlerMetricBag($this->all('avg_response_duration_ms'));
    }

    public HandlerMetricBag $responseDurationSecondsBucketLe250ms {
        get => $this->responseDurationSecondsBucketLe250ms ??= new HandlerMetricBag($this->all('response_duration_seconds_bucket_le_250ms'));
    }

    /**
     * Delta of $responseDurationSecondsBucketLe250ms
     */
    public HandlerMetricBag $responseDurationSecondsBucketLe250msDelta {
        get => $this->responseDurationSecondsBucketLe250msDelta ??= new HandlerMetricBag($this->all('response_duration_seconds_bucket_le_250ms_delta'));
    }

    /**
     * % of requests < 250ms
     */
    public private(set) ?HandlerMetricBag $responseDurationSecondsBucketLe250msPecents = null {
        get {
            if ($this->responseDurationSecondsBucketLe250msPecents) {
                return $this->responseDurationSecondsBucketLe250msPecents;
            }

            $percents = [];

            foreach (['php', 'file_server'] as $handler) {
                $deltaHandler = $this->responseDurationSecondsBucketLe250msDelta->get($handler);
                $deltaNbRequests = $this->requestsTotalDelta->get($handler);

                $percents[$handler] = $deltaHandler !== null && $deltaNbRequests > 0 ? round($deltaHandler / $deltaNbRequests * 100, 2) : null;
            }

            return $this->responseDurationSecondsBucketLe250msPecents = new HandlerMetricBag($percents);
        }
    }

    public HandlerMetricBag $requestDurationSecondsSum {
        get => $this->requestDurationSecondsSum ??= new HandlerMetricBag($this->all('request_duration_seconds_sum'));
    }

    /**
     * Computed from the deltas of requestDurationSecondsSum and requestsTotal
     */
    public HandlerMetricBag $avgRequestDurationMs {
        get => $this->avgRequestDurationMs ??= new HandlerMetricBag($this->all('avg_request_duration_ms'));
    }

    public HandlerMetricBag $requestSizeBytesSum {
        get => $this->requestSizeBytesSum ??= new HandlerMetricBag($this->all('request_size_bytes_sum'));
    }

    /**
     * Delta
     */
    public HandlerMetricBag $requestSizeBytesSumDelta {
        get => $this->requestSizeBytesSumDelta ??= new HandlerMetricBag($this->all('request_size_bytes_sum_delta'));
    }

    public ?HandlerMetricBag $requestSizePerSec = null {
        get {
            if ($this->requestSizePerSec) {
                return $this->requestSizePerSec;
            }

            $values = [];

            foreach (['php', 'file_server'] as $handler) {
                $deltaSize = $this->requestSizeBytesSumDelta->get($handler);

                $values[$handler] = $deltaSize !== null && $this->elapsedTime > 0 ? round($deltaSize / $this->elapsedTime, 2) : null;
            }

            return $this->requestSizePerSec = new HandlerMetricBag($values);
        }
    }

    /**
     * Computed from the deltas of requestSizeBytesSum and requestsTotal (int)
     */
    public HandlerMetricBag $avgRequestSizeBytes {
        get => $this->avgRequestSizeBytes ??= new HandlerMetricBag($this->all('avg_request_size_bytes'));
    }

    public HandlerMetricBag $responseSizeBytesSum {
        get => $this->responseSizeBytesSum ??= new HandlerMetricBag($this->all('response_size_bytes_sum'));
    }

    /**
     * Delta
     */
    public HandlerMetricBag $responseSizeBytesSumDelta {
        get => $this->responseSizeBytesSumDelta ??= new HandlerMetricBag($this->all('response_size_bytes_sum_delta'));
    }

    public ?HandlerMetricBag $responseSizePerSec = null {
        get {
            if ($this->responseSizePerSec) {
                return $this->responseSizePerSec;
            }

            $values = [];

            foreach (['php', 'file_server'] as $handler) {
                $deltaSize = $this->responseSizeBytesSumDelta->get($handler);

                $values[$handler] = $deltaSize !== null && $this->elapsedTime > 0 ? round($deltaSize / $this->elapsedTime, 2) : null;
            }

            return $this->responseSizePerSec = new HandlerMetricBag($values);
        }
    }

    /**
     * Computed from the deltas of responseSizeBytesSum and requestsTotal (int)
     */
    public HandlerMetricBag $avgResponseSizeBytes {
        get => $this->avgResponseSizeBytes ??= new HandlerMetricBag($this->all('avg_response_size_bytes'));
    }

    public ?float $processCpuSecondsTotal {
        get => $this->getFloat('process_cpu_seconds_total');
    }

    /**
     * Delta, not rounded (to get the current CPU time %)
     */
    public ?float $processCpuSecondsTotalDelta {
        get => $this->getFloat('process_cpu_seconds_total_delta');
    }

    /**
     * Number of seconds between this push (the values in this bag) and the previous one
     */
    public ?int $elapsedTime {
        get => $this->getInt('elapsed_time');
    }

    public ?float $processCpuSecondsTotalPercent {
        get => $this->processCpuSecondsTotalDelta !== null && $this->elapsedTime > 0 ? round($this->processCpuSecondsTotalDelta / $this->elapsedTime * 100, 2) : null;
    }

    public ?int $processResidentMemoryBytes {
        get => $this->getInt('process_resident_memory_bytes');
    }

    public ?int $uptime {
        get => $this->getInt('process_start_time_seconds') !== null ? time() - $this->getInt('process_start_time_seconds') : null;
    }
}
