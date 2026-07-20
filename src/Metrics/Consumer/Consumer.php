<?php

declare(strict_types=1);

namespace App\Metrics\Consumer;

use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\Bag\Caddy\CaddyBag;
use App\Metrics\Dto\Bag\FrankenPhp\FrankenPhpBag;
use App\Metrics\Dto\Bag\Mysql\MysqlInfoSchemaBag;
use App\Metrics\Dto\Bag\Mysql\MysqlQueriesCountBag;
use App\Metrics\Dto\Bag\Mysql\MysqlSlowQueriesBag;
use App\Metrics\Dto\Bag\Mysql\MysqlStatusBag;
use App\Metrics\Dto\Bag\Mysql\MysqlVariableBag;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\Bag\Postgres\PostgresActivityBag;
use App\Metrics\Dto\Bag\Postgres\PostgresDatabaseBag;
use App\Metrics\Dto\Bag\Postgres\PostgresSettingsBag;
use App\Metrics\Dto\Bag\Postgres\PostgresSlowQueriesBag;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\Bag\System\SystemBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\MetricBagDto;

enum Consumer: string
{
    case SYSTEM = 'system';
    case APACHE = 'apache';
    case NGINX = 'nginx';
    case CADDY = 'caddy';
    case MYSQL_STATUS = 'mysql.status';
    case MYSQL_VARIABLES = 'mysql.variables';
    case MYSQL_SLOW_QUERIES = 'mysql.slow_queries';
    case MYSQL_QUERY_COUNT = 'mysql.queries_count';
    case MYSQL_INFO_SCHEMA = 'mysql.information_schema';
    case POSTGRES_SETTINGS = 'postgresql.settings';
    case POSTGRES_ACTIVITY = 'postgresql.activity';
    case POSTGRES_DATABASE = 'postgresql.database';
    case POSTGRES_SLOW_QUERIES = 'postgresql.slow_queries';
    case PHP = 'php';
    case REDIS = 'redis';
    case SYMFONY = 'symfony';
    case FRANKENPHP = 'frankenphp';

    /**
     * Pour validator symfony
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn(self $consumer) => $consumer->value, self::cases());
    }

    public function getCacheKey(Project $project): string
    {
        // the bucketId is used in the cache key rather than e.g. the project id:
        // this "clears" the cache along with a bucket clear (the project's bucket id changes, so the cache too)
        return \sprintf('METRICS.%s.%s', mb_strtoupper($this->value), $project->getBucketId());
    }

    public function getComponent(): Component
    {
        return match ($this) {
            self::SYSTEM => Component::System,
            self::APACHE => Component::Apache,
            self::MYSQL_STATUS,
            self::MYSQL_QUERY_COUNT,
            self::MYSQL_VARIABLES,
            self::MYSQL_SLOW_QUERIES,
            self::MYSQL_INFO_SCHEMA => Component::MySQL,
            self::POSTGRES_SETTINGS,
            self::POSTGRES_ACTIVITY,
            self::POSTGRES_DATABASE,
            self::POSTGRES_SLOW_QUERIES => Component::Postgres,
            self::PHP => Component::PHP,
            self::REDIS => Component::Redis,
            self::NGINX => Component::Nginx,
            self::CADDY => Component::Caddy,
            self::SYMFONY => Component::Symfony,
            self::FRANKENPHP => Component::FrankenPHP,
        };
    }

    /**
     * @return class-string<MetricBagDto>
     */
    public function metricBagClass(): string
    {
        return match ($this) {
            self::SYSTEM => SystemBag::class,
            self::APACHE => ApacheBag::class,
            self::MYSQL_QUERY_COUNT => MysqlQueriesCountBag::class,
            self::MYSQL_STATUS => MysqlStatusBag::class,
            self::MYSQL_INFO_SCHEMA => MysqlInfoSchemaBag::class,
            self::PHP => PhpBag::class,
            self::REDIS => RedisBag::class,
            self::NGINX => NginxBag::class,
            self::CADDY => CaddyBag::class,
            self::MYSQL_VARIABLES => MysqlVariableBag::class,
            self::MYSQL_SLOW_QUERIES => MysqlSlowQueriesBag::class,
            self::POSTGRES_SETTINGS => PostgresSettingsBag::class,
            self::POSTGRES_ACTIVITY => PostgresActivityBag::class,
            self::POSTGRES_DATABASE => PostgresDatabaseBag::class,
            self::POSTGRES_SLOW_QUERIES => PostgresSlowQueriesBag::class,
            self::SYMFONY => SymfonyBag::class,
            self::FRANKENPHP => FrankenPhpBag::class,
        };
    }
}
