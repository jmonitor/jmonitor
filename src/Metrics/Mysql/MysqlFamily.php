<?php

declare(strict_types=1);

namespace App\Metrics\Mysql;

enum MysqlFamily: string
{
    case MySQL = 'mysql';
    case Mariadb = 'mariadb';
    case Percona = 'percona';

    public function label(): string
    {
        return match ($this) {
            self::MySQL => 'MySQL',
            self::Mariadb => 'MariaDB',
            self::Percona => 'Percona',
        };
    }

    public static function tryFromVersionComment(?string $versionComment): ?self
    {
        if ($versionComment === null) {
            return null;
        }

        foreach (['mariadb', 'percona', 'mysql'] as $family) {
            if (str_contains(mb_strtolower($versionComment), $family)) {
                return self::from($family);
            }
        }

        return null;
    }
}
