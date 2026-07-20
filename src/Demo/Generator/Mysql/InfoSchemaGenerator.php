<?php

declare(strict_types=1);

namespace App\Demo\Generator\Mysql;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class InfoSchemaGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::MYSQL_INFO_SCHEMA;
    }

    public function generate(DemoState $state): array
    {
        // Data length drifts slowly upward: 200–600 MB in bytes, low volatility
        $dataLength = (int) $state->walk('mysql.is_data_length', 200_000_000, 600_000_000, 0.01);
        // Index length is roughly 30% of data length
        $indexLength = (int) ($dataLength * 0.3);

        return [
            'schema_name' => 'demo_shop',
            'information_schema_readable' => true,
            'data_weight' => [
                'data_length' => $dataLength,
                'index_length' => $indexLength,
            ],
        ];
    }
}
