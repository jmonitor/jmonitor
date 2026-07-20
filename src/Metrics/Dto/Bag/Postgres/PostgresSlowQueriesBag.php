<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\MetricBagDto;

class PostgresSlowQueriesBag extends MetricBagDto
{
    public ?int $minCalls { get => $this->getInt('min_calls'); }
    public ?float $minAvgTimeMs { get => $this->getFloat('min_avg_time_ms'); }
    public ?int $limit { get => $this->getInt('limit'); }
    public ?string $orderBy { get => $this->get('order_by'); }

    /**
     * @var PostgresSlowQueryBag[]|null
     */
    public ?array $slowQueries {
        get => $this->slowQueries ?? $this->buildSlowQueries();
    }

    /**
     * @return PostgresSlowQueryBag[]|null
     */
    private function buildSlowQueries(): ?array
    {
        if ($this->get('slow_queries') === null) {
            return null;
        }

        $items = [];

        foreach ($this->all('slow_queries') as $data) {
            $items[] = new PostgresSlowQueryBag($data);
        }

        return $items;
    }
}
