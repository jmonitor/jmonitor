<?php

declare(strict_types=1);

use App\Metrics\Metric;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;

/**
 * Configuration of value ranges (Range) for metrics.
 * Each metric can have several ranges defining its state.
 *
 * Format: [min, max, BadgeStyle, label, meaning, notes],
 */
return [
    Metric::SystemRamUsage->value => [
        'ranges' => [
            [0, 70, BadgeStyle::SUCCESS, 'Healthy', 'Memory usage is normal. The system has enough free memory to handle traffic spikes and background processes.'],
            [70, 85, BadgeStyle::WARNING, 'Warning', 'Memory usage is getting high. The system may start relying more on cache eviction or swap under load.'],
            [85, 100, BadgeStyle::DANGER, 'Critical', 'Memory usage is critically high. The system is at risk of performance degradation or crashes.'],
        ],
        'note' => null,
    ],
    Metric::SystemCpuUsage->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Healthy',
                'CPU usage is low to moderate. The system can handle current workloads without performance issues.',
            ],
            [
                60,
                80,
                BadgeStyle::WARNING,
                'Warning',
                'CPU usage is high. The system may experience slower response times under load.',
            ],
            [
                80,
                100,
                BadgeStyle::DANGER,
                'Critical',
                'CPU usage is critically high. The system is at risk of severe performance degradation or unresponsive services.',
            ],
        ],
        'note' => 'Short spikes are normal, but sustained high CPU usage usually indicates overloaded or inefficient processes.',
    ],
    Metric::SystemDiskUsage->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Disk usage is low. There is enough free space for logs, uploads, backups, and system operations.',
            ],
            [
                70,
                85,
                BadgeStyle::WARNING,
                'Warning',
                'Disk usage is high. Free space is decreasing and should be monitored to avoid issues.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Critical',
                'Disk usage is critically high. The system may fail to write logs, uploads, or database data.',
            ],
        ],
        'note' => 'Running out of disk space can cause services to crash or stop unexpectedly.',
    ],
    Metric::ApacheBusyWorkers->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Most Apache workers are free. The server can handle incoming requests without delays.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'Warning',
                'Many Apache workers are busy. New requests may start to queue during traffic peaks.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Critical',
                'Almost all Apache workers are busy. New requests may be delayed or rejected.',
            ],
        ],
        'note' => 'Sustained saturation usually indicates insufficient workers or slow requests.',
    ],
    Metric::ApacheBusyProcess->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Stable',
                'Apache is using a normal number of processes. The server has enough capacity to handle incoming requests.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'Under Pressure',
                'A high number of Apache processes are active. Resource usage is increasing and should be monitored.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Overloaded',
                'Most Apache processes are busy. The server may struggle to accept new connections.',
            ],
        ],
        'note' => 'High process usage often correlates with high traffic or slow request processing.',
    ],
    Metric::PhpOpcacheMemory->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Healthy',
                'OPcache has enough free memory to store compiled PHP scripts efficiently.',
            ],
            [
                70,
                90,
                BadgeStyle::WARNING,
                'Cache Almost Full',
                'OPcache memory usage is high. Script eviction may occur, reducing performance.',
            ],
            [
                90,
                100,
                BadgeStyle::DANGER,
                'Cache Full',
                'OPcache memory is full. PHP scripts may be recompiled frequently, causing performance issues.',
            ],
        ],
        'note' => 'When OPcache is full, increasing opcache.memory_consumption is usually recommended.',
    ],
    Metric::PhpOpcacheHitRate->value => [
        'ranges' => [
            [
                95,
                100,
                BadgeStyle::SUCCESS,
                'Optimal',
                'Most PHP scripts are served from OPcache. Performance is optimal.',
            ],
            [
                85,
                95,
                BadgeStyle::WARNING,
                'Suboptimal',
                'OPcache is effective but misses occur regularly. Performance may be impacted.',
            ],
            [
                0,
                85,
                BadgeStyle::DANGER,
                'Inefficient',
                'Many PHP scripts are not served from cache. This can significantly reduce performance.',
            ],
        ],
        'note' => 'After restarting Apache or PHP-FPM, the hit rate may be low for a few minutes while the cache warms up.',
    ],
    Metric::PhpFpmActiveProcesses->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Most PHP-FPM processes are idle. The server can handle incoming PHP requests efficiently.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'High Load',
                'Many PHP-FPM processes are active. Requests may start to queue during traffic peaks.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Saturated',
                'Almost all PHP-FPM processes are busy. New PHP requests may be delayed or rejected.',
            ],
        ],
        'note' => 'Sustained saturation usually indicates insufficient PHP-FPM workers or slow PHP requests.',
    ],
    Metric::PhpFpmMemoryPeakPercent->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Safe',
                'PHP memory usage is well below the configured memory limit.',
            ],
            [
                70,
                90,
                BadgeStyle::WARNING,
                'Close to Limit',
                'PHP scripts are using a large portion of the memory limit. Some requests may fail under heavy load.',
            ],
            [
                90,
                100,
                BadgeStyle::DANGER,
                'Memory Limit Risk',
                'PHP scripts are very close to the memory limit. Memory exhaustion errors are likely.',
            ],
        ],
        'note' => 'High memory peaks often indicate inefficient code or an insufficient PHP memory_limit.',
    ],
    Metric::PhpApcuMemoryUsage->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Enough Cache',
                'APCu has enough free memory to store application data efficiently.',
            ],
            [
                70,
                90,
                BadgeStyle::WARNING,
                'Cache Almost Full',
                'APCu memory usage is high. Cache entries may start to be evicted.',
            ],
            [
                90,
                100,
                BadgeStyle::DANGER,
                'Cache Full',
                'APCu memory is full. Cached data is frequently evicted, reducing performance benefits.',
            ],
        ],
        'note' => 'When APCu is full, increasing apc.shm_size or reducing cached entries may be necessary.',
    ],
    Metric::PhpApcuHitRate->value => [
        'ranges' => [
            [
                95,
                100,
                BadgeStyle::SUCCESS,
                'Efficient',
                'Most requests are served from APCu cache. Cache efficiency is excellent.',
            ],
            [
                85,
                95,
                BadgeStyle::WARNING,
                'Partially Efficient',
                'APCu cache is working, but misses occur regularly. Performance gains may be limited.',
            ],
            [
                0,
                85,
                BadgeStyle::DANGER,
                'Inefficient',
                'Many requests are not served from APCu cache. Cache effectiveness is low.',
            ],
        ],
        'note' => 'After a cache flush or application restart, the hit rate may be low until the cache warms up.',
    ],
    Metric::NginxActiveConnections->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Available Capacity',
                'Most connection slots are free. Nginx can handle additional incoming connections without issues.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'High Load',
                'Many connection slots are in use. New connections may start to queue during traffic spikes.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Connection Limit Risk',
                'The number of active connections is close to the configured limit. New connections may be rejected.',
            ],
        ],
        'note' => 'Sustained high usage may indicate traffic spikes, slow upstream responses, or insufficient worker_connections.',
    ],
    Metric::FrankenPhpBusyThreadsPercent->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Available Capacity',
                'Most FrankenPHP threads are idle. The server can handle additional PHP requests without delay.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'High Load',
                'Many FrankenPHP threads are busy. New requests may start to queue during traffic spikes.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Saturated',
                'Almost all FrankenPHP threads are busy. New PHP requests may be delayed or rejected.',
            ],
        ],
        'note' => 'Sustained saturation usually indicates insufficient threads or slow PHP request processing.',
    ],
    Metric::MysqlConnectionsUsage->value => [
        'ranges' => [
            [
                0,
                60,
                BadgeStyle::SUCCESS,
                'Available Capacity',
                'Most database connection slots are free. The server can handle additional database requests.',
            ],
            [
                60,
                85,
                BadgeStyle::WARNING,
                'High Usage',
                'Many database connections are in use. Connection pressure may increase during traffic peaks.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Connection Limit Risk',
                'The number of active connections is close to the configured maximum. New connections may be refused.',
            ],
        ],
        'note' => 'If the limit is reached, MySQL will return "Too many connections" errors.',
    ],
    Metric::MysqlInnodbBufferPoolUsage->value => [
        'ranges' => [
            [
                90,
                100,
                BadgeStyle::INFO,
                'Warm / Loaded',
                'The buffer pool is full and effectively caching data. This is the ideal state for a production server.',
            ],
            [
                30,
                90,
                BadgeStyle::INFO,
                'Warming Up',
                'The cache is filling up or the database is smaller than the allocated memory. Performance is scaling.',
            ],
            [
                0,
                30,
                BadgeStyle::WARNING,
                'Cold / Underused',
                'The cache is mostly empty. This may happen after a restart or if the buffer pool is significantly oversized.',
            ],
        ],
        'note' => 'High usage is normal for a database. Unlike CPU, 100% usage is not a bottleneck but a sign of a "warm" cache. Monitor the Hit Rate to determine if more RAM is needed.',
    ],
    Metric::MysqlInnodbBufferPoolHitRate->value => [
        'ranges' => [
            [
                98,
                100,
                BadgeStyle::SUCCESS,
                'Optimal',
                'Almost all database reads are served from the InnoDB buffer pool. Disk access is minimal.',
            ],
            [
                90,
                98,
                BadgeStyle::WARNING,
                'Suboptimal',
                'Some database reads require disk access. The buffer pool may be slightly undersized.',
            ],
            [
                0,
                90,
                BadgeStyle::DANGER,
                'Poor Cache Efficiency',
                'Many reads are performed from disk instead of memory. Query performance may be significantly reduced.',
            ],
        ],
        'note' => 'Higher is better. A low hit rate usually indicates that the buffer pool is too small for the dataset or that some queries are performing large scans without using indexes.',
    ],
    Metric::MysqlTablesLockWaitsPercent->value => [
        'ranges' => [
            [
                0,
                1,
                BadgeStyle::SUCCESS,
                'Minimal Contention',
                'Table lock waits are rare. Queries can acquire table locks without noticeable delays.',
            ],
            [
                1,
                5,
                BadgeStyle::WARNING,
                'Lock Contention',
                'Some queries must wait for table locks. This may indicate increasing concurrency pressure.',
            ],
            [
                5,
                100,
                BadgeStyle::DANGER,
                'High Lock Contention',
                'Many queries are waiting for table locks. This can significantly impact database performance.',
            ],
        ],
        'note' => 'High lock waits may occur with MyISAM tables, long transactions, or queries locking tables for extended periods.',
    ],
    Metric::MysqlThreadCacheMiss->value => [
        'ranges' => [
            [
                0,
                1,
                BadgeStyle::SUCCESS,
                'Efficient',
                'Most connections reuse existing threads from the cache. Thread creation overhead is minimal.',
            ],
            [
                1,
                5,
                BadgeStyle::WARNING,
                'Moderate Miss Rate',
                'Some connections require new threads. The thread cache may be slightly undersized.',
            ],
            [
                5,
                100,
                BadgeStyle::DANGER,
                'High Miss Rate',
                'Many connections require new threads. This may increase connection overhead and CPU usage.',
            ],
        ],
        'note' => 'A high miss rate usually indicates that thread_cache_size is too small for the current connection rate.',
    ],
    Metric::MysqlTmpTables->value => [
        'ranges' => [
            [
                0,
                10,
                BadgeStyle::SUCCESS,
                'Mostly In-Memory',
                'Most temporary tables are created in memory. Query performance is optimal.',
            ],
            [
                10,
                25,
                BadgeStyle::WARNING,
                'Frequent Disk Tables',
                'Some temporary tables are created on disk. Query performance may be affected.',
            ],
            [
                25,
                100,
                BadgeStyle::DANGER,
                'Disk-Heavy Queries',
                'Many temporary tables are created on disk. This can significantly slow down queries.',
            ],
        ],
        'note' => 'A high percentage usually indicates large result sets, missing indexes, or insufficient tmp_table_size and max_heap_table_size.',
    ],
    Metric::PostgresConnectionsUsage->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Available Capacity',
                'Most connection slots are free. PostgreSQL can accept new client connections without pressure.',
            ],
            [
                70,
                85,
                BadgeStyle::WARNING,
                'High Usage',
                'A large share of connection slots are in use. A traffic surge could exhaust the remaining slots.',
            ],
            [
                85,
                100,
                BadgeStyle::DANGER,
                'Connection Limit Risk',
                'The number of active connections is close to max_connections. New connections may be rejected.',
            ],
        ],
        'note' => 'Each PostgreSQL connection is a separate OS process. When max_connections is reached, new connections are refused. A connection pooler such as PgBouncer is usually a better fix than simply raising the limit.',
    ],
    Metric::PostgresRollbackRatio->value => [
        'ranges' => [
            [
                0,
                5,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Almost all transactions commit successfully. Rollbacks are rare.',
            ],
            [
                5,
                10,
                BadgeStyle::WARNING,
                'Elevated Rollbacks',
                'A noticeable share of transactions are rolled back. This may indicate application errors or failing queries.',
            ],
            [
                10,
                100,
                BadgeStyle::DANGER,
                'High Rollback Rate',
                'Many transactions never complete successfully, wasting CPU and I/O. Application errors or deadlocks are likely.',
            ],
        ],
        'note' => 'Some frameworks and health checks intentionally roll back transactions, which can inflate this ratio without indicating a real problem. Know your application baseline before raising an alert.',
    ],
    Metric::PostgresCacheHitRatio->value => [
        'ranges' => [
            [
                99,
                100,
                BadgeStyle::SUCCESS,
                'Optimal',
                'Almost all block reads are served from PostgreSQL\'s shared buffers. Disk I/O is minimal.',
            ],
            [
                95,
                99,
                BadgeStyle::WARNING,
                'Suboptimal',
                'Some reads are served from disk. The working set may not fully fit in shared buffers.',
            ],
            [
                0,
                95,
                BadgeStyle::DANGER,
                'Poor Cache Efficiency',
                'Many reads go to disk instead of memory, which can significantly slow down queries.',
            ],
        ],
        'note' => 'Higher is better. A freshly restarted database shows a low ratio until its cache warms up, which is normal. This only measures PostgreSQL\'s own buffer cache; the operating system also caches files, so real disk reads are even lower.',
    ],
    Metric::PostgresDeadTupleRatio->value => [
        'ranges' => [
            [
                0,
                10,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Dead rows are kept low. Autovacuum is keeping up with the write churn.',
            ],
            [
                10,
                20,
                BadgeStyle::WARNING,
                'Bloat Building Up',
                'Dead rows are accumulating. Autovacuum may be starting to fall behind on busy tables.',
            ],
            [
                20,
                100,
                BadgeStyle::DANGER,
                'High Bloat',
                'Dead rows make up a large share of the table. Tables and indexes are bloating and queries scan more pages.',
            ],
        ],
        'note' => 'PostgreSQL leaves obsolete row versions behind on every UPDATE/DELETE until autovacuum reclaims them. The default autovacuum_vacuum_scale_factor triggers a vacuum around 20% dead rows. A long-running transaction can block cleanup across the whole database.',
    ],
    Metric::PostgresIndexUsageRatio->value => [
        'ranges' => [
            [
                95,
                100,
                BadgeStyle::SUCCESS,
                'Optimal',
                'Most row fetches go through an index. Queries are taking the fast path.',
            ],
            [
                80,
                95,
                BadgeStyle::WARNING,
                'Frequent Sequential Scans',
                'A notable share of fetches use sequential scans. Some queries may be missing useful indexes.',
            ],
            [
                0,
                80,
                BadgeStyle::DANGER,
                'Index Underused',
                'Many queries fall back to full table scans, which get slower as the data grows.',
            ],
        ],
        'note' => 'A sequential scan is not always bad: on small tables, or when a query genuinely needs most of the rows, PostgreSQL deliberately chooses a full scan. A low ratio is only a problem when large, frequently queried tables are being scanned in full.',
    ],
    Metric::RedisMemoryUsage->value => [
        'ranges' => [
            [
                0,
                70,
                BadgeStyle::SUCCESS,
                'Healthy',
                'Redis memory usage is within a safe range. There is enough capacity for cached data.',
            ],
            [
                70,
                90,
                BadgeStyle::WARNING,
                'Memory Pressure',
                'Redis memory usage is high. Keys may start to be evicted depending on the configured policy.',
            ],
            [
                90,
                100,
                BadgeStyle::DANGER,
                'Eviction Risk',
                'Redis memory usage is near the configured limit. Key eviction or write errors may occur.',
            ],
        ],
        'note' => 'When the memory limit is reached, Redis will evict keys or reject writes depending on the maxmemory-policy configuration.',
    ],
    Metric::RedisHitRate->value => [
        'ranges' => [
            [
                95,
                100,
                BadgeStyle::SUCCESS,
                'Efficient',
                'Most requests are served directly from Redis cache. Cache efficiency is excellent.',
            ],
            [
                80,
                95,
                BadgeStyle::WARNING,
                'Moderate Efficiency',
                'Redis cache is effective but misses occur regularly. Some requests fall back to the database.',
            ],
            [
                0,
                80,
                BadgeStyle::DANGER,
                'Low Cache Efficiency',
                'Many requests are missing the cache. Redis may provide limited performance benefits.',
            ],
        ],
        'note' => 'Higher is better. A low hit rate may indicate short TTLs, frequent cache invalidation, or ineffective caching strategies.',
    ],
];
