<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Postgres;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::POSTGRES_SETTINGS->value)]
class PostgresSettingsConsumer implements ConsumerInterface
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
        return [];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'server_version'                  => [new Assert\Type('string'), new Assert\Length(max: 128)],
                'max_connections'                 => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(1)],
                // Memory settings are converted to bytes by the collector.
                'shared_buffers'                  => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'effective_cache_size'            => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'work_mem'                        => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'maintenance_work_mem'            => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'wal_level'                       => [new Assert\Type('string'), new Assert\Length(max: 32)],
                'max_wal_size'                    => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'checkpoint_completion_target'    => [new Assert\Type('numeric')],
                'random_page_cost'                => [new Assert\Type('numeric')],
                'effective_io_concurrency'        => [new Assert\Type('numeric')],
                'log_min_duration_statement'      => [new Assert\Type('numeric')],
                'TimeZone'                        => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'autovacuum'                      => [new Assert\Type('string'), new Assert\Choice(choices: ['on', 'off'])],
                'autovacuum_vacuum_scale_factor'  => [new Assert\Type('numeric')],
                'track_counts'                    => [new Assert\Type('string'), new Assert\Choice(choices: ['on', 'off'])],
            ],
            allowExtraFields: false,
            allowMissingFields: true,
        );
    }
}
