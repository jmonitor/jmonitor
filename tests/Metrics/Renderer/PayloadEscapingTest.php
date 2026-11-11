<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Consumer\Apache\ApacheConsumer;
use App\Metrics\Consumer\Caddy\CaddyConsumer;
use App\Metrics\Consumer\FrankenPhp\FrankenPhpConsumer;
use App\Metrics\Consumer\Mysql\MysqlInformationSchemaConsumer;
use App\Metrics\Consumer\Mysql\StatusConsumer;
use App\Metrics\Consumer\Mysql\VariablesConsumer;
use App\Metrics\Consumer\Nginx\NginxConsumer;
use App\Metrics\Consumer\Php\PhpConsumer;
use App\Metrics\Consumer\Postgres\PostgresDatabaseConsumer;
use App\Metrics\Consumer\Postgres\PostgresSettingsConsumer;
use App\Metrics\Consumer\Redis\RedisConsumer;
use App\Metrics\Consumer\Symfony\SymfonyConsumer;
use App\Metrics\Consumer\System\SystemConsumer;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A pushed string is text, never markup, whatever card shows it.
 */
final class PayloadEscapingTest extends PayloadCardTestCase
{
    public function testSystemInformations(): void
    {
        $this->push(SystemConsumer::class, [
            'hostname' => '<b>web-01</b>',
            'timezone' => '<b>UTC</b>',
            'os' => ['pretty_name' => '<b>Debian</b>', 'uptime' => 3600],
        ]);

        $this->assertEscaped(3, $this->renderCard(Metric::SystemInformations));
    }

    public function testApacheInformations(): void
    {
        $this->push(ApacheConsumer::class, [
            'server_version' => '<b>Apache/2.4.62</b>',
            'server_mpm' => '<b>event</b>',
            'workers' => ['busy' => 1, 'idle' => 1],
            'scoreboard' => [],
        ]);

        $this->assertEscaped(2, $this->renderCard(Metric::ApacheInformations));
    }

    public function testNginxConfig(): void
    {
        $this->push(NginxConsumer::class, [
            'config' => [
                'config_path' => '<b>/etc/nginx/nginx.conf</b>',
                'user' => '<b>www-data</b>',
                'access_log' => '<b>/var/log/nginx/access.log</b>',
                'error_log' => '<b>/var/log/nginx/error.log</b>',
                'ssl_protocols' => '<b>TLSv1.3</b>',
            ],
        ]);

        $this->assertEscaped(4, $this->renderCard(Metric::NginxCoreConfig));
        $this->assertEscaped(1, $this->renderCard(Metric::NginxOtherConfig));
    }

    public function testMysqlVariables(): void
    {
        $this->push(VariablesConsumer::class, [
            'version' => '<b>8.4.3</b>',
            'time_zone' => '<b>SYSTEM</b>',
            'system_time_zone' => '<b>UTC</b>',
            'slow_query_log_file' => '<b>/var/log/mysql/slow.log</b>',
            'log_bin' => '<b>ON</b>',
            'character_set_server' => '<b>utf8mb4</b>',
            'collation_server' => '<b>utf8mb4_0900_ai_ci</b>',
        ]);
        $this->push(StatusConsumer::class, ['Uptime' => 3600]);

        $this->assertEscaped(2, $this->renderCard(Metric::MysqlInformations));
        $this->assertEscaped(4, $this->renderCard(Metric::MysqlConfig));
        $this->assertEscaped(1, $this->renderCard(Metric::MysqlVersion));
    }

    public function testPostgresSettings(): void
    {
        $this->push(PostgresSettingsConsumer::class, [
            'wal_level' => '<b>replica</b>',
            'TimeZone' => '<b>UTC</b>',
        ]);

        $this->assertEscaped(1, $this->renderCard(Metric::PostgresConfig));
        $this->assertEscaped(1, $this->renderCard(Metric::PostgresTuning));
    }

    public function testPhpConfig(): void
    {
        $this->push(PhpConsumer::class, [
            'sapi_name' => '<b>fpm-fcgi</b>',
            'error_log' => '<b>/var/log/php.log</b>',
            'post_max_size' => '<b>8M</b>',
            'upload_max_filesize' => '<b>2M</b>',
            'date.timezone' => '<b>UTC</b>',
        ]);

        $this->assertEscaped(4, $this->renderCard(Metric::PhpBaseConfig));
        $this->assertEscaped(1, $this->renderCard(Metric::PhpSapi));
    }

    public function testRedisServer(): void
    {
        $this->push(RedisConsumer::class, [
            'server' => ['version' => '<b>7.4</b>', 'mode' => '<b>standalone</b>'],
        ]);

        $this->assertEscaped(1, $this->renderCard(Metric::RedisMode));
        $this->assertEscaped(1, $this->renderCard(Metric::RedisVersion));
    }

    public function testSymfonyEnvironmentAndVersion(): void
    {
        $this->push(SymfonyConsumer::class, [
            'env' => '<b>prod</b>',
            'version' => '<b>8</b>',
        ]);

        $environment = $this->renderCard(Metric::SymfonyEnvironment);
        $this->assertStringNotContainsStringIgnoringCase('<b>', $environment);
        $this->assertStringContainsString('&lt;B&gt;PROD', $environment);

        $this->assertEscaped(1, $this->renderCard(Metric::SymfonyVersion));
    }

    public function testVersionCards(): void
    {
        $this->push(NginxConsumer::class, ['version' => '<b>1</b>']);
        $this->push(CaddyConsumer::class, ['version' => '<b>2</b>']);
        $this->push(FrankenPhpConsumer::class, ['version' => '<b>1</b>']);
        $this->push(PhpConsumer::class, ['version' => '<b>8.4</b>']);

        $this->assertEscaped(1, $this->renderCard(Metric::NginxVersion));
        $this->assertEscaped(1, $this->renderCard(Metric::CaddyVersion));
        $this->assertEscaped(1, $this->renderCard(Metric::FrankenPhpVersion));
        $this->assertEscaped(1, $this->renderCard(Metric::PhpVersion));
    }

    /**
     * The unit of a formatted amount is markup of ours, and has to stay markup.
     */
    public function testFormattedAmountsKeepTheirUnit(): void
    {
        $this->push(VariablesConsumer::class, ['innodb_buffer_pool_size' => 134217728, 'sort_buffer_size' => 262144]);
        $this->push(MysqlInformationSchemaConsumer::class, ['data_weight' => ['data_length' => 2048, 'index_length' => 1024]]);
        $this->push(PostgresSettingsConsumer::class, ['work_mem' => 4194304, 'max_wal_size' => 1073741824]);
        $this->push(PostgresDatabaseConsumer::class, ['db_size' => 8388608, 'tables' => ['total_size' => 4194304, 'indexes_size' => 1048576]]);
        $this->push(RedisConsumer::class, ['persistence' => ['aof_enabled' => true, 'aof_current_size' => 2048, 'aof_rewrite_base_size' => 1024]]);
        $this->push(PhpConsumer::class, ['fpm' => ['memory-peak' => 2097152]]);
        $this->push(CaddyConsumer::class, ['requests_total' => ['php' => 10], 'request_size_bytes_sum' => ['php' => 1000], 'response_size_bytes_sum' => ['php' => 1000]], '-10 seconds');
        $this->push(CaddyConsumer::class, ['requests_total' => ['php' => 20], 'request_size_bytes_sum' => ['php' => 21480], 'response_size_bytes_sum' => ['php' => 41960]]);

        $this->assertSame(['128 MiB', '256 KiB'], self::amounts($this->renderCard(Metric::MysqlConfig)));
        $this->assertSame(['2 KiB', '1 KiB', '3 KiB'], self::amounts($this->renderCard(Metric::MysqlDataWeightDetail)));
        $this->assertSame(['4 MiB', '1 GiB'], self::amounts($this->renderCard(Metric::PostgresConfig)));
        $this->assertSame(['4 MiB', '1 MiB', '8 MiB'], self::amounts($this->renderCard(Metric::PostgresStorageBreakdown)));
        $this->assertSame(['2 KiB'], self::amounts($this->renderCard(Metric::RedisPersistence)));
        $this->assertSame(['2 KiB'], self::amounts($this->renderCard(Metric::CaddyAvgRequestSize, options: ['handler' => 'php']), '.consumer_value-value'));
        $this->assertSame(['4 KiB'], self::amounts($this->renderCard(Metric::CaddyAvgResponseSize, options: ['handler' => 'php']), '.consumer_value-value'));
        $this->assertSame('2 MiB', trim(new Crawler($this->renderCard(Metric::PhpFpmMemoryPeakValue, Renderer::ConsumerValue))->filter('.consumer_value-value')->text()));
    }

    private function assertEscaped(int $expected, string $html): void
    {
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertSame($expected, substr_count($html, '&lt;b&gt;'));
    }

    /**
     * @return list<string> the text of every element holding a unit, e.g. "128 MiB"
     */
    private static function amounts(string $html, string $selector = '.dt'): array
    {
        return new Crawler($html)->filter($selector)->reduce(static fn(Crawler $node): bool => $node->filter('span.unit')->count() > 0)->each(static fn(Crawler $node): string => trim($node->text()));
    }
}
