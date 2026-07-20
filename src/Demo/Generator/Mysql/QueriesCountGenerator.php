<?php

declare(strict_types=1);

namespace App\Demo\Generator\Mysql;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class QueriesCountGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::MYSQL_QUERY_COUNT;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();

        return [
            'schema_name' => 'demo_shop',
            'total_select_queries' => (int) $state->counter('mysql.qc_select', (int) round(rand(300, 12000) * $season)),
            'total_insert_queries' => (int) $state->counter('mysql.qc_insert', (int) round(rand(50, 3000) * $season)),
            'total_update_queries' => (int) $state->counter('mysql.qc_update', (int) round(rand(50, 4000) * $season)),
            'total_delete_queries' => (int) $state->counter('mysql.qc_delete', (int) round(rand(10, 800) * $season)),
        ];
    }
}
