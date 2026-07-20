<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\MetricBagDto;

class MysqlInfoSchemaBag extends MetricBagDto
{
    public ?string $schemaName {
        get => $this->get('schema_name');
    }

    public ?bool $infoSchemaReadable {
        get => $this->getBool('information_schema_readable');
    }

    // not worth a dedicated bag class
    public array $dataWeight {
        get {
            $dataWeight = $this->all('data_weight');
            $dataWeight['data_length'] = isset($dataWeight['data_length']) ? (int) $dataWeight['data_length'] : null;
            $dataWeight['index_length'] = isset($dataWeight['index_length']) ? (int) $dataWeight['index_length'] : null;
            $dataWeight['total_length'] = null;

            if ($dataWeight['data_length'] !== null && $dataWeight['index_length'] !== null) {
                $dataWeight['total_length'] = $dataWeight['data_length'] + $dataWeight['index_length'];
            }

            return $dataWeight;
        }
    }
}
