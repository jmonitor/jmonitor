<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class PhpGenerator implements DemoMetricGeneratorInterface
{
    private const int OPCACHE_TOTAL = 128 * 1024 * 1024;       // opcache.memory_consumption
    private const int OPCACHE_INTERNED = 16 * 1024 * 1024;     // opcache.interned_strings_buffer
    private const int OPCACHE_JIT = 64 * 1024 * 1024;          // opcache.jit_buffer_size
    private const int APCU_SEGMENT = 32 * 1024 * 1024;         // apc.shm_size

    public function getConsumer(): Consumer
    {
        return Consumer::PHP;
    }

    public function generate(DemoState $state): array
    {
        return [
            'version' => '8.4.8',
            'sapi_name' => 'fpm-fcgi',
            'ini_file' => '/etc/php/8.4/fpm/php.ini',
            'ini_files' => [
                '/etc/php/8.4/fpm/conf.d/10-opcache.ini',
                '/etc/php/8.4/fpm/conf.d/20-apcu.ini',
                '/etc/php/8.4/fpm/conf.d/20-redis.ini',
            ],
            'expose_php' => false,
            'memory_limit' => '256M',
            'max_execution_time' => 30,
            'max_input_time' => 60,
            'max_input_vars' => 1000,
            'realpath_cache_used_size' => (int) round($state->walk('php.realpath_used', 200_000.0, 4_000_000.0, 0.03)),
            'realpath_cache_size' => '4096K',
            'realpath_cache_ttl' => 120,
            'post_max_size' => '32M',
            'upload_max_filesize' => '24M',
            'display_errors' => 'stderr',
            'display_startup_errors' => false,
            'log_errors' => true,
            'error_log' => '/var/log/php/fpm-error.log',
            'error_reporting' => \E_ALL,
            'date.timezone' => 'UTC',
            'loaded_extensions' => [
                'Core', 'date', 'json', 'pcre', 'standard', 'mysqlnd',
                'curl', 'mbstring', 'openssl', 'pdo_mysql', 'redis',
                'Zend OPcache', 'apcu',
            ],
            'opcache' => $this->opcache($state),
            'apcu' => $this->apcu($state),
            'fpm' => $this->fpm($state),
        ];
    }

    /**
     * Shape mirrors Bag\Php\Opcache\* : config{directives,version} + status{memory_usage,
     * interned_strings_usage, opcache_statistics, jit}.
     *
     * @return array<string, mixed>
     */
    private function opcache(DemoState $state): array
    {
        $season = $state->seasonality();

        $usedPercent = max(20.0, min(92.0, $state->walk('php.opcache_used', 35.0, 78.0, 0.03) * $season));
        $used = (int) round(self::OPCACHE_TOTAL * $usedPercent / 100);
        $wasted = (int) round($state->walk('php.opcache_wasted', 0.0, 4_000_000.0, 0.05));
        $free = max(0, self::OPCACHE_TOTAL - $used - $wasted);

        $internedUsed = (int) round($state->walk('php.opcache_interned_used', 2_000_000.0, 8_000_000.0, 0.03));
        $internedFree = max(0, self::OPCACHE_INTERNED - $internedUsed);

        $jitUsed = (int) round($state->walk('php.opcache_jit_used', 5_000_000.0, 40_000_000.0, 0.03));
        $jitFree = max(0, self::OPCACHE_JIT - $jitUsed);

        $hits = (int) $state->counter('php.opcache_hits', (int) round(rand(2000, 8000) * $season));
        $misses = (int) $state->counter('php.opcache_misses', rand(0, 5));
        $hitRate = round($hits / max(1, $hits + $misses) * 100, 2);

        $cachedScripts = (int) round($state->walk('php.opcache_scripts', 900.0, 1400.0, 0.02));

        return [
            'config' => [
                'directives' => [
                    'opcache.enable' => true,
                    'opcache.enable_cli' => false,
                    'opcache.memory_consumption' => self::OPCACHE_TOTAL,
                    'opcache.interned_strings_buffer' => self::OPCACHE_INTERNED,
                    'opcache.max_accelerated_files' => 16229,
                    'opcache.validate_timestamps' => false,
                    'opcache.revalidate_freq' => 2,
                    'opcache.preload' => '',
                ],
                'version' => [
                    'version' => '8.4.8',
                    'opcache_product_name' => 'Zend OPcache',
                ],
                'blacklist' => [],
            ],
            'status' => [
                'opcache_enabled' => true,
                'cache_full' => false,
                'restart_pending' => false,
                'restart_in_progress' => false,
                'memory_usage' => [
                    'used_memory' => $used,
                    'free_memory' => $free,
                    'wasted_memory' => $wasted,
                    'current_wasted_percentage' => round($wasted / self::OPCACHE_TOTAL * 100, 2),
                ],
                'interned_strings_usage' => [
                    'buffer_size' => self::OPCACHE_INTERNED,
                    'used_memory' => $internedUsed,
                    'free_memory' => $internedFree,
                    'number_of_strings' => (int) round($state->walk('php.opcache_interned_strings', 8000.0, 30000.0, 0.03)),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => $cachedScripts,
                    'num_cached_keys' => $cachedScripts + rand(50, 200),
                    'max_cached_keys' => 16229,
                    'hits' => $hits,
                    'start_time' => time() - 3600 * 24,
                    'last_restart_time' => 0,
                    'oom_restarts' => 0,
                    'hash_restarts' => 0,
                    'manual_restarts' => 0,
                    'misses' => $misses,
                    'blacklist_misses' => 0,
                    'blacklist_miss_ratio' => 0,
                    'opcache_hit_rate' => $hitRate,
                ],
                'jit' => [
                    'enabled' => true,
                    'on' => true,
                    'kind' => 5,
                    'opt_level' => 4,
                    'opt_flags' => 6,
                    'buffer_size' => self::OPCACHE_JIT,
                    'buffer_free' => $jitFree,
                ],
            ],
        ];
    }

    /**
     * Shape mirrors Bag\Php\Apcu\* : config + cache_info + sma_info.
     *
     * @return array<string, mixed>
     */
    private function apcu(DemoState $state): array
    {
        $season = $state->seasonality();

        $used = (int) round($state->walk('php.apcu_used', 5_000_000.0, 26_000_000.0, 0.04) * $season);
        $used = min(self::APCU_SEGMENT - 1_000_000, max(1_000_000, $used));
        $avail = max(0, self::APCU_SEGMENT - $used);

        $hits = (int) $state->counter('php.apcu_hits', (int) round(rand(500, 4000) * $season));
        $misses = (int) $state->counter('php.apcu_misses', rand(0, 50));

        return [
            'config' => [
                'apc.enabled' => true,
                'apc.enable_cli' => false,
                'apc.shm_size' => '32M',
                'apc.shm_segments' => 1,
                'apc.ttl' => 0,
            ],
            'cache_info' => [
                'num_hits' => $hits,
                'num_misses' => $misses,
                'num_entries' => (int) round($state->walk('php.apcu_entries', 200.0, 1800.0, 0.03)),
                'memory_type' => 'mmap',
            ],
            'sma_info' => [
                'num_seg' => 1,
                'seg_size' => self::APCU_SEGMENT,
                'avail_mem' => $avail,
            ],
        ];
    }

    /**
     * Shape mirrors Bag\Php\Opcache\FpmBag (and src/Dev/PhpCollector.php).
     *
     * @return array<string, mixed>
     */
    private function fpm(DemoState $state): array
    {
        $season = $state->seasonality();

        $active = max(1, (int) round($state->walk('php.fpm_active', 1.0, 18.0, 0.1) * $season));

        return [
            'pool' => 'www',
            'process-manager' => 'dynamic',
            'start-since' => 3600 * 24,
            'accepted-conn' => (int) $state->counter('php.fpm_accepted', (int) round(rand(20, 200) * $season)),
            'idle-processes' => (int) round($state->walk('php.fpm_idle', 1, 10, 0.15)),
            'active-processes' => $active,
            'max-active-processes' => max($active, rand(10, 20)),
            'max-children-reached' => 0,
            'slow-requests' => (int) $state->counter('php.fpm_slow', rand(0, 100) > 95 ? 1 : 0),
            'memory-peak' => (int) round($state->walk('php.fpm_mem', 5_000_000.0, 50_000_000.0, 0.05)),
        ];
    }
}
