<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Redis;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Plan\PlanResolver;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * https://redis.io/docs/latest/commands/info/
 */
#[AsTaggedItem(Consumer::REDIS->value)]
class RedisConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly DeltaCalculator $deltaCalculator,
        private readonly PlanResolver $planResolver,
    ) {}

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        $delta = $this->deltaCalculator->getDelta($bag);

        $data = $bag->all();
        $data['stats'] ??= [];
        $data['stats']['ops_per_sec'] = $delta?->getPerSec('stats.total_commands_processed');

        return $data;
    }

    /**
     * @param RedisBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $points = array_merge([$this->getRedisPoint($bag)], $this->getDbPoints($bag));

        return array_filter($points);
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'server' => new Assert\Type('array'),
                'clients' => new Assert\Type('array'),
                'memory' => new Assert\Type('array'),
                'persistence' => new Assert\Type('array'),
                'stats' => new Assert\Type('array'),
                'replication' => new Assert\Type('array'),
                'cpu' => new Assert\Type('array'),
                'databases' => new Assert\Type('array'),
                'config' => new Assert\Type('array'),
            ],
            allowMissingFields: true,
        );
    }

    private function getRedisPoint(RedisBag $bag): ?Point
    {
        $datas = array_filter([
            'used_memory' => $bag->memory->used,
            // used_rss ?
            'total_connections_received' => $bag->stats->totalConnectionsReceived,
            'total_commands_processed' => $bag->stats->totalCommandsProcessed,
            'cpu_used_sys' => $bag->cpu->usedSys,
            'cpu_used_user' => $bag->cpu->usedUser,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return null;
        }

        $point = new Point('redis');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return $point;
    }

    private function getDbPoints(RedisBag $bag): array
    {
        $points = [];

        $databases = array_slice($bag->databases, 0, $this->planResolver->resolve($bag->getProject())->nbRedisDb());

        foreach ($databases as $i => $db) {
            if ($db->keys === null) {
                continue;
            }

            $points[] = new Point('redis_db', tags: ['db' => $i], fields: [
                'keys' => $db->keys,
            ]);
        }

        return $points;
    }
}
