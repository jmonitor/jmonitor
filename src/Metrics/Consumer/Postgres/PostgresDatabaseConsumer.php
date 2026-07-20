<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Postgres;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\Postgres\PostgresDatabaseBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::POSTGRES_DATABASE->value)]
class PostgresDatabaseConsumer implements ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function getInfluxPoints(MetricBagDto $bag): array
    {
        if (!$bag instanceof PostgresDatabaseBag) {
            return [];
        }

        $fields = [
            'db_size'      => $bag->dbSize,
            'total_size'   => $bag->totalSize,
            'indexes_size' => $bag->indexesSize,
            'live_tuples'  => $bag->liveTuples,
            'dead_tuples'  => $bag->deadTuples,
            'seq_scans'    => $bag->seqScans,
            'idx_scans'    => $bag->idxScans,
        ];

        $fields = array_filter($fields, static fn($v): bool => $v !== null);

        if (!$fields) {
            return [];
        }

        $point = new Point('postgres_database');
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
                'schema'  => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'db_size' => $numericPositive,
                'tables'  => new Collection(
                    fields: [
                        'live_tuples'  => $numericPositive,
                        'dead_tuples'  => $numericPositive,
                        'seq_scans'    => $numericPositive,
                        'idx_scans'    => $numericPositive,
                        'total_size'   => $numericPositive,
                        'indexes_size' => $numericPositive,
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
