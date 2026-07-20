<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Postgres;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Postgres\PostgresActivityBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::POSTGRES_ACTIVITY->value)]
class PostgresActivityConsumer implements ConsumerInterface
{
    public function __construct(private readonly DeltaCalculator $deltaCalculator) {}

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        $delta = $this->deltaCalculator->getDelta($bag);

        if (!$delta) {
            return $bag;
        }

        $data = $bag->all();

        $commit = $delta->getPerSec('database_stats.xact_commit');
        $rollback = $delta->getPerSec('database_stats.xact_rollback');
        $data['transactions_per_second'] = $commit !== null || $rollback !== null
            ? round(($commit ?? 0) + ($rollback ?? 0), 2)
            : null;

        $data['deadlocks_per_second'] = $delta->getPerSec('database_stats.deadlocks');

        return $bag->withParameters($data);
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function getInfluxPoints(MetricBagDto $bag): array
    {
        if (!$bag instanceof PostgresActivityBag) {
            return [];
        }

        $fields = [
            'xact_commit'                => $bag->xactCommit,
            'xact_rollback'              => $bag->xactRollback,
            'blks_read'                  => $bag->blksRead,
            'blks_hit'                   => $bag->blksHit,
            'tup_inserted'               => $bag->tupInserted,
            'tup_updated'                => $bag->tupUpdated,
            'tup_deleted'                => $bag->tupDeleted,
            'deadlocks'                  => $bag->deadlocks,
            'temp_files'                 => $bag->tempFiles,
            'temp_bytes'                 => $bag->tempBytes,
            'numbackends'                => $bag->numbackends,
            'conn_active'                => $bag->connections['active'] ?? null,
            'conn_idle'                  => $bag->connections['idle'] ?? null,
            'conn_idle_in_transaction'   => $bag->connections['idle in transaction'] ?? null,
            'blocked_count'              => $bag->blockedCount,
            'oldest_transaction_seconds' => $bag->oldestTransactionSeconds,
        ];

        $fields = array_filter($fields, static fn($v): bool => $v !== null);

        $point = new Point('postgres_activity');

        foreach ($fields as $key => $value) {
            $point->addField($key, $value);
        }

        return [$point];
    }

    public function getConstraints(int $version): Constraint|array
    {
        $numericPositive = [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)];

        return new Collection(
            fields: [
                'database_stats' => new Collection(
                    fields: [
                        'numbackends'   => $numericPositive,
                        'xact_commit'   => $numericPositive,
                        'xact_rollback' => $numericPositive,
                        'blks_read'     => $numericPositive,
                        'blks_hit'      => $numericPositive,
                        'tup_returned'  => $numericPositive,
                        'tup_fetched'   => $numericPositive,
                        'tup_inserted'  => $numericPositive,
                        'tup_updated'   => $numericPositive,
                        'tup_deleted'   => $numericPositive,
                        'conflicts'     => $numericPositive,
                        'deadlocks'     => $numericPositive,
                        'temp_files'    => $numericPositive,
                        'temp_bytes'    => $numericPositive,
                    ],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
                'bgwriter'    => [new Assert\Type('array')],
                'connections' => [new Assert\Type('array')],
                'sessions'    => new Collection(
                    fields: [
                        'oldest_transaction_seconds'         => [new Assert\Type('numeric')],
                        'idle_in_transaction_count'          => $numericPositive,
                        'oldest_idle_in_transaction_seconds' => [new Assert\Type('numeric')],
                        'blocked_count'                      => $numericPositive,
                        'max_wait_seconds'                   => [new Assert\Type('numeric')],
                        'blocked_queries'                    => [new Assert\Type('array')],
                    ],
                    allowExtraFields: true,
                    allowMissingFields: true,
                ),
            ],
            allowExtraFields: true,
            allowMissingFields: true,
        );
    }
}
