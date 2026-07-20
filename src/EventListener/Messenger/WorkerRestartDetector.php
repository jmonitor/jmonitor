<?php

declare(strict_types=1);

namespace App\EventListener\Messenger;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;

/**
 * Writes a sentinel file when the worker is stopped by messenger:stop-workers.
 * The worker-wrapper.sh script reads this file to avoid restarting the worker
 * during deployments (as opposed to natural stops that should trigger a restart).
 *
 * @see .clevercloud/worker-wrapper.sh
 */
#[AsEventListener(event: WorkerStartedEvent::class, method: 'onStarted')]
#[AsEventListener(event: WorkerStoppedEvent::class, method: 'onStopped')]
class WorkerRestartDetector
{
    private int $startedAt = 0;

    public function __construct(
        #[Autowire(service: 'cache.messenger.restart_workers_signal')]
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function onStarted(): void
    {
        $this->startedAt = time();
    }

    public function onStopped(): void
    {
        $sentinelFile = getenv('CC_WORKER_SENTINEL_FILE');

        if ($sentinelFile === false || $sentinelFile === '') {
            return;
        }

        $item = $this->cache->getItem(StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY);

        if ($item->isHit() && $item->get() > $this->startedAt) {
            touch($sentinelFile);
        }
    }
}
