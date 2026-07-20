<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Mysql;

enum SlowQueriesOrderBy: string
{
    case SUM = 'sum';
    case AVG = 'avg';
    case MAX = 'max';

    public static function formChoices(): array
    {
        return [
            self::SUM->value,
            self::AVG->value,
            self::MAX->value,
        ];
    }
}
