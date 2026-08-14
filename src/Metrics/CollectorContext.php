<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\LastPush\LastPushBag;
use App\Metrics\LastPush\LastPushManager;
use App\Metrics\LastPush\LastPushStatus;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use App\Version\CollectorVersion;

/**
 * Gathers information about the "state" of the current project's collector.
 */
readonly class CollectorContext
{
    public function __construct(
        private LastPushManager $lastPushManager,
        private ProjectContext $projectContext,
        private PlanResolver $planResolver,
    ) {}

    public function getLastPushBag(): ?LastPushBag
    {
        return $this->lastPushManager->getLastPushBag();
    }

    public function getCollectorVersion(): CollectorVersion
    {
        return new CollectorVersion($this->getLastPushBag()?->collectorVersion);
    }

    public function getLastPushStatus(): LastPushStatus
    {
        if (!$this->projectContext->getCurrentProject()) {
            throw new \RuntimeException('No project context');
        }

        $lastPushBag = $this->lastPushManager->getLastPushBag();

        // after 120 sec the collector is considered inactive
        if ($lastPushBag && $lastPushBag->elapsedSeconds < 120) {
            $planPushInterval = $this->planResolver->resolve($this->projectContext->getCurrentProject())->pushInterval();

            if ($lastPushBag->elapsedSeconds <= $planPushInterval) {
                return LastPushStatus::ACTIVE;
            }

            return LastPushStatus::LATE;
        }

        return LastPushStatus::INACTIVE;
    }
}
