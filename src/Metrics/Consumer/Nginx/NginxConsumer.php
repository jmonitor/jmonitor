<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Nginx;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::NGINX->value)]
class NginxConsumer implements ConsumerInterface
{
    private DeltaCalculator $deltaCalculator;

    public function __construct(DeltaCalculator $deltaCalculator)
    {
        $this->deltaCalculator = $deltaCalculator;
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        $delta = $this->deltaCalculator->getDelta($bag);

        $datas = $bag->all();
        $datas['status'] ??= [];
        $datas['status']['requests_per_second'] = $delta?->getPerSec('status.requests');

        return $datas;
    }

    /**
     * @param NginxBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $status = $bag->status;

        $datas = array_filter([
            'active' => $status->activeConnections,
            'requests' => $status->httpRequests,
            'waiting' => $status->waitingConnections,
            'active_rate' => $status->activeConnectionsPercent,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return [];
        }

        $point = new Point('nginx');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return [$point];
    }

    /**
     * @return Constraint|Constraint[]
     */
    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'version' => [new Assert\Type('string'), new Assert\Length(max: 10)],
                'modules' => [
                    new Assert\Type('array'),
                    new Assert\Count(max: 60),
                    new Assert\All([new Assert\Type('string')]),
                ],
                'status' => new Collection(
                    fields: [
                        'active' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'accepts' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'handled' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'requests' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'reading' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'writing' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'waiting' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                    allowMissingFields: true,
                ),
                'config' => new Collection(
                    fields: [
                        'config_path' => new Assert\Type('string'),
                        'user' => new Assert\Type('string'),
                        'worker_processes' => new Assert\Type('string'),
                        'include' => [new Assert\Type('array'), new Assert\All([new Assert\Type('string')])],
                        'worker_connections' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'sendfile' => new Assert\Choice(choices: ['on', 'off', '1', '0']),
                        'tcp_nopush' => new Assert\Choice(choices: ['on', 'off', '1', '0']),
                        'tcp_nodelay' => new Assert\Choice(choices: ['on', 'off', '1', '0']),
                        'keepalive_timeout' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'types_hash_max_size' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'server_tokens' => new Assert\Type('string'),
                        'ssl_protocols' => new Assert\Type('string'),
                        'ssl_prefer_server_ciphers' => new Assert\Choice(choices: ['on', 'off', '1', '0']),
                        'access_log' => new Assert\Type('string'),
                        'error_log' => new Assert\Type('string'),
                        'gzip' => new Assert\Type('string'),
                    ],
                    allowMissingFields: true,
                ),
                'cpu_count' => new Assert\Type('int'),
            ],
            allowMissingFields: true,
        );
    }
}
