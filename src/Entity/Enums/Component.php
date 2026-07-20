<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use App\Alerting\AlertMetric;
use App\Metrics\Consumer\Consumer;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Component: string implements TranslatableInterface
{
    case System = 'system';
    case PHP = 'php';
    case Apache = 'apache';
    case Nginx = 'nginx';
    case Caddy = 'caddy';
    case FrankenPHP = 'frankenphp';
    case MySQL = 'mysql';
    case Postgres = 'postgres';
    // case SQLite = 'sqlite';
    case Redis = 'redis';
    // case Memcached = 'memcached';
    case Symfony = 'symfony';

    public static function alphaOrderedCases(): array
    {
        return [
            self::Apache,
            self::Caddy,
            self::FrankenPHP,
            self::MySQL,
            self::Nginx,
            self::PHP,
            self::Postgres,
            self::Redis,
            self::Symfony,
            self::System,
        ];
    }

    public static function menuOrderedCases(): array
    {
        return [
            self::System,
            self::Apache,
            self::Nginx,
            self::Caddy,
            self::PHP,
            self::FrankenPHP,
            self::Symfony,
            self::MySQL,
            self::Postgres,
            self::Redis,
        ];
    }

    public static function pricingOrderedCases(): array
    {
        return [
            self::System,
            self::PHP,
            self::Apache,
            self::MySQL,
            self::Postgres,
            self::Nginx,
            self::Symfony,
            self::Caddy,
            self::Redis,
            self::FrankenPHP,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::PHP => 'PHP',
            self::Apache => 'Apache',
            self::Nginx => 'Nginx',
            self::Caddy => 'Caddy',
            self::MySQL => 'MySQL',
            self::Postgres => 'PostgreSQL',
            self::Redis => 'Redis',
            self::Symfony => 'Symfony',
            self::FrankenPHP => 'FrankenPHP',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::System => 'material-symbols:dns-outline',
            self::PHP => 'devicon-plain:php',
            self::Apache => 'devicon-plain:apache',
            self::Nginx => 'simple-icons:nginx',
            self::Caddy => 'simple-icons:caddy',
            self::MySQL => 'lineicons:mysql',
            self::Postgres => 'devicon-plain:postgresql',
            self::Redis => 'devicon-plain:redis',
            self::Symfony => 'mdi:symfony',
            self::FrankenPHP => 'devicon-plain:frankenphp',
        };
    }

    // hardcoded order for now; could become sortable later
    public function menuPosition(): int
    {
        return array_search($this, self::menuOrderedCases()) + 1;
    }

    /**
     * @return iterable<AlertMetric>
     */
    public function alertMetrics(): iterable
    {
        return AlertMetric::byComponent($this);
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }

    /**
     * @return Consumer[]
     */
    public function consumers(): array
    {
        return match ($this) {
            self::System => [Consumer::SYSTEM],
            self::PHP => [Consumer::PHP],
            self::Apache => [Consumer::APACHE],
            self::Nginx => [Consumer::NGINX],
            self::Caddy => [Consumer::CADDY],
            self::MySQL => [Consumer::MYSQL_STATUS, Consumer::MYSQL_QUERY_COUNT, Consumer::MYSQL_VARIABLES],
            self::Postgres => [Consumer::POSTGRES_SETTINGS, Consumer::POSTGRES_ACTIVITY, Consumer::POSTGRES_DATABASE, Consumer::POSTGRES_SLOW_QUERIES],
            self::Redis => [Consumer::REDIS],
            self::Symfony => [Consumer::SYMFONY],
            self::FrankenPHP => [Consumer::FRANKENPHP],
        };
    }

    public function eolUrl(): ?string
    {
        return match ($this) {
            self::PHP => 'https://endoflife.date/api/php.json',
            self::Apache => 'https://endoflife.date/api/apache-http-server.json',
            self::Nginx => 'https://endoflife.date/api/nginx.json',
            self::Caddy => 'https://endoflife.date/api/caddy.json',
            self::MySQL => 'https://endoflife.date/api/mysql.json',
            self::Redis => 'https://endoflife.date/api/redis.json',
            self::Symfony => 'https://endoflife.date/api/symfony.json',
            self::Postgres => 'https://endoflife.date/api/postgresql.json',
            default => null,
        };
    }
}
