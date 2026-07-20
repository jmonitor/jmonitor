<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Caddy;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Caddy\CaddyBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::CADDY->value)]
readonly class CaddyConsumer implements ConsumerInterface
{
    public function __construct(
        private DeltaCalculator $deltaCalculator,
    ) {}

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        $delta = $this->deltaCalculator->getDelta($bag);

        if ($delta) {
            $data = $bag->all();

            foreach (['php', 'file_server'] as $handler) {
                $requestsTotalDelta = $delta->getValue('requests_total.' . $handler);

                if ($requestsTotalDelta === null || $requestsTotalDelta <= 0) {
                    continue;
                }

                $data['requests_total_delta'][$handler] = $requestsTotalDelta;

                // Average Request Duration
                $requestDurationSecondsSumDelta = $delta->getValue('request_duration_seconds_sum.' . $handler);

                if ($requestDurationSecondsSumDelta !== null) {
                    $data['avg_request_duration_ms'][$handler] = (int) round($requestDurationSecondsSumDelta / $requestsTotalDelta * 1000);
                }

                // avg response duration
                $responseDurationSecondsSumDelta = $delta->getValue('response_duration_seconds_sum.' . $handler);

                if ($responseDurationSecondsSumDelta !== null) {
                    $data['avg_response_duration_ms'][$handler] = (int) round($responseDurationSecondsSumDelta / $requestsTotalDelta * 1000);
                }

                // avg request size & delta de request_size_bytes_sum
                $requestSizeBytesSumDelta = $delta->getValue('request_size_bytes_sum.' . $handler);

                if ($requestSizeBytesSumDelta !== null) {
                    $data['request_size_bytes_sum_delta'][$handler] = $requestSizeBytesSumDelta;
                    $data['avg_request_size_bytes'][$handler] = (int) round($requestSizeBytesSumDelta / $requestsTotalDelta);
                }

                // avg response size & delta de response_size_bytes_sum
                $responseSizeBytesSumDelta = $delta->getValue('response_size_bytes_sum.' . $handler);

                if ($responseSizeBytesSumDelta !== null) {
                    $data['response_size_bytes_sum_delta'][$handler] = $responseSizeBytesSumDelta;
                    $data['avg_response_size_bytes'][$handler] = (int) round($responseSizeBytesSumDelta / $requestsTotalDelta);
                }

                // delta of response_duration_seconds_bucket_le_250ms
                $responseDurationSecondsBucketLe250msDelta = $delta->getValue('response_duration_seconds_bucket_le_250ms.' . $handler);

                if ($responseDurationSecondsBucketLe250msDelta !== null) {
                    $data['response_duration_seconds_bucket_le_250ms_delta'][$handler] = $responseDurationSecondsBucketLe250msDelta;
                }

                // req per sec
                $data['requests_per_second'][$handler] = $delta->getPerSec('requests_total.' . $handler);
            }

            // CPU time delta
            $data['process_cpu_seconds_total_delta'] = $delta->getValue('process_cpu_seconds_total');

            // elapsed time since last push
            $data['elapsed_time'] = $delta->elapsedTime;

            return $bag->withMetrics($data);
        }

        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    /**
     * @param CaddyBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $points = array_merge($this->getHandlerPoints($bag), [$this->getCaddyPoint($bag)]);

        return array_filter($points);
    }

    /**
     * @return Constraint|Constraint[]
     */
    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'version' => [new Assert\Type('string'), new Assert\Length(max: 10)],
                'requests_total' => new Collection(
                    fields: [
                        'php' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'requests_in_flight' => new Collection(
                    fields: [
                        'php' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'response_size_bytes_sum' => new Collection(
                    fields: [
                        'php' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'response_duration_seconds_sum' => new Collection(
                    fields: [
                        'php' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'response_duration_seconds_bucket_le_250ms' => new Collection(
                    fields: [
                        'php' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'request_duration_seconds_sum' => new Collection(
                    fields: [
                        'php' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type(['float', 'int', 'null']), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'request_size_bytes_sum' => new Collection(
                    fields: [
                        'php' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'file_server' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                        'static_response' => [new Assert\Type(['int', 'null']), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
                'process_cpu_seconds_total' => [new Assert\Type(['float', 'int']), new Assert\GreaterThanOrEqual(0)],
                'process_resident_memory_bytes' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                'process_start_time_seconds' => [new Assert\Type(['int']), new Assert\GreaterThanOrEqual(1704137761)],
            ],
            allowMissingFields: true,
        );
    }

    private function getCaddyPoint(CaddyBag $bag): ?Point
    {
        $datas = array_filter([
            'process_cpu_seconds_total' => $bag->processCpuSecondsTotal,
            'process_cpu_seconds_total_percent' => $bag->processCpuSecondsTotalPercent,
            'process_resident_memory_bytes' => $bag->processResidentMemoryBytes,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return null;
        }

        $point = new Point('caddy');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return $point;
    }

    private function getHandlerPoints(CaddyBag $bag): array
    {
        $points = [];

        foreach (['php', 'file_server'] as $handlerName) {
            $data = [
                'requests_total' => $bag->requestsTotal->get($handlerName),
                'avg_response_duration_ms' => $bag->avgResponseDurationMs->get($handlerName),
                'avg_request_duration_ms' => $bag->avgRequestDurationMs->get($handlerName),
                'response_size_bytes_sum' => $bag->responseSizeBytesSum->get($handlerName),
                'request_size_bytes_sum' => $bag->requestSizeBytesSum->get($handlerName),
            ];

            $data = array_filter($data, fn($v): bool => $v !== null);

            if (!$data) {
                continue;
            }

            $points[] = new Point('caddy_handler', tags: ['handler' => $handlerName], fields: $data);
        }

        return $points;
    }
}
