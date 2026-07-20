<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\MetricBagDto;

class MysqlSlowQueriesBag extends MetricBagDto
{
    public ?string $schemaName {
        get => $this->get('schema_name');
    }

    public ?int $minExecCount {
        get => $this->getInt('min_exec_count');
    }

    public ?int $minAvgTimeMs {
        get => $this->getInt('min_avg_time_ms');
    }

    public ?int $limit {
        get => $this->getInt('limit');
    }

    public ?SlowQueriesOrderBy $orderBy {
        get => SlowQueriesOrderBy::tryFrom($this->get('order_by') ?? '_');
    }

    /**
     * @var MysqlSlowQueryBag[]|null
     */
    public ?array $slowQueries {
        get => $this->slowQueries ?? $this->getSlowQueries();
    }

    /**
     * @return MysqlSlowQueryBag[]|null
     */
    private function getSlowQueries(): ?array
    {
        // "no slow queries" ([]) is different from "the metric was not sent" (null)
        if ($this->get('slow_queries') === null) {
            return null;
        }

        $items = [];

        foreach ($this->all('slow_queries') as $data) {
            $items[] = new MysqlSlowQueryBag($data);
        }

        return $items;
    }
}
