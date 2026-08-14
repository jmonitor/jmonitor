<?php

declare(strict_types=1);

namespace App\Metrics\LastPush;

use App\Event\PostConsumeEvent;
use App\Project\ProjectContext;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Stores information about the last push in a cache.
 * Lets us know (and display)
 * - whether (and when) data was received from the project's collector
 * - the collector version
 */
class LastPushManager implements ResetInterface
{
    private LastPushBag|false|null $lastPushBag = false;

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly ProjectContext $projectContext,
    ) {}

    public function saveLastPush(PostConsumeEvent $event): void
    {
        $data = [
            'received_at' => $event->receivedAt->getTimestamp(),
            'collector_version' => $event->jmonitorVersion,
            'bundle_version' => $event->bundleVersion,
        ];

        $item = $this->cacheItemPool->getItem(sprintf('last_push_%s', $event->project->getId()));

        $item->set($data);
        $item->expiresAfter(86400); // 24h
        $this->cacheItemPool->save($item);
    }

    public function getLastPushBag(): ?LastPushBag
    {
        if ($this->lastPushBag !== false) {
            return $this->lastPushBag;
        }

        $data = $this->cacheItemPool->getItem(sprintf('last_push_%s', $this->projectContext->getCurrentProject()->getId()));

        if (!$data->isHit()) {
            return $this->lastPushBag = null;
        }

        return $this->lastPushBag = new LastPushBag($data->get());
    }

    public function reset(): void
    {
        $this->lastPushBag = false;
    }
}
