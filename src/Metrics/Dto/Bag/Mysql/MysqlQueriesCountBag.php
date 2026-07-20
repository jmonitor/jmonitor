<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

use App\Metrics\Dto\MetricBagDto;

class MysqlQueriesCountBag extends MetricBagDto
{
    public ?string $dbName {
        get => $this->get('schema_name');
    }

    public ?int $nbSelect {
        get => $this->get('total_select_queries');
    }

    public ?int $nbInsert {
        get => $this->get('total_insert_queries');
    }

    public ?int $nbUpdate {
        get => $this->get('total_update_queries');
    }

    public ?int $nbDelete {
        get => $this->get('total_delete_queries');
    }
}
