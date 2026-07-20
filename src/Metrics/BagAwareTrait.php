<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\Bag\Caddy\CaddyBag;
use App\Metrics\Dto\Bag\FrankenPhp\FrankenPhpBag;
use App\Metrics\Dto\Bag\Mysql\MysqlInfoSchemaBag;
use App\Metrics\Dto\Bag\Mysql\MysqlSlowQueriesBag;
use App\Metrics\Dto\Bag\Mysql\MysqlStatusBag;
use App\Metrics\Dto\Bag\Mysql\MysqlVariableBag;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\Bag\Postgres\PostgresActivityBag;
use App\Metrics\Dto\Bag\Postgres\PostgresDatabaseBag;
use App\Metrics\Dto\Bag\Postgres\PostgresSettingsBag;
use App\Metrics\Dto\Bag\Postgres\PostgresSlowQueriesBag;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\Bag\System\SystemBag;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * syntaxic sugar
 */
trait BagAwareTrait
{
    private MetricsBagProvider $bagProvider;

    #[Required]
    public function setBagProvider(MetricsBagProvider $bagProvider): void
    {
        $this->bagProvider = $bagProvider;
    }

    public function getApacheBag(): ?ApacheBag
    {
        return $this->bagProvider->getLastBag(Consumer::APACHE, ApacheBag::class);
    }

    public function getCaddyBag(): ?CaddyBag
    {
        return $this->bagProvider->getLastBag(Consumer::CADDY, CaddyBag::class);
    }

    public function getFrankenPhpBag(): ?FrankenPhpBag
    {
        return $this->bagProvider->getLastBag(Consumer::FRANKENPHP, FrankenPhpBag::class);
    }

    public function getMysqlVariablesBag(): ?MysqlVariableBag
    {
        return $this->bagProvider->getLastBag(Consumer::MYSQL_VARIABLES, MysqlVariableBag::class);
    }

    public function getMysqlStatusBag(): ?MysqlStatusBag
    {
        return $this->bagProvider->getLastBag(Consumer::MYSQL_STATUS, MysqlStatusBag::class);
    }

    public function getMysqlSlowQueriesBag(): ?MysqlSlowQueriesBag
    {
        return $this->bagProvider->getLastBag(Consumer::MYSQL_SLOW_QUERIES, MysqlSlowQueriesBag::class);
    }

    public function getMysqlInfoSchemaBag(): ?MysqlInfoSchemaBag
    {
        return $this->bagProvider->getLastBag(Consumer::MYSQL_INFO_SCHEMA, MysqlInfoSchemaBag::class);
    }

    public function getPostgresSettingsBag(): ?PostgresSettingsBag
    {
        return $this->bagProvider->getLastBag(Consumer::POSTGRES_SETTINGS, PostgresSettingsBag::class);
    }

    public function getPostgresActivityBag(): ?PostgresActivityBag
    {
        return $this->bagProvider->getLastBag(Consumer::POSTGRES_ACTIVITY, PostgresActivityBag::class);
    }

    public function getPostgresDatabaseBag(): ?PostgresDatabaseBag
    {
        return $this->bagProvider->getLastBag(Consumer::POSTGRES_DATABASE, PostgresDatabaseBag::class);
    }

    public function getPostgresSlowQueriesBag(): ?PostgresSlowQueriesBag
    {
        return $this->bagProvider->getLastBag(Consumer::POSTGRES_SLOW_QUERIES, PostgresSlowQueriesBag::class);
    }

    public function getNginxBag(): ?NginxBag
    {
        return $this->bagProvider->getLastBag(Consumer::NGINX, NginxBag::class);
    }

    public function getPhpBag(): ?PhpBag
    {
        return $this->bagProvider->getLastBag(Consumer::PHP, PhpBag::class);
    }

    public function getRedisBag(): ?RedisBag
    {
        return $this->bagProvider->getLastBag(Consumer::REDIS, RedisBag::class);
    }

    public function getSymfonyBag(): ?SymfonyBag
    {
        return $this->bagProvider->getLastBag(Consumer::SYMFONY, SymfonyBag::class);
    }

    public function getSystemBag(): ?SystemBag
    {
        return $this->bagProvider->getLastBag(Consumer::SYSTEM, SystemBag::class);
    }
}
