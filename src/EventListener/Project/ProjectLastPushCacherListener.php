<?php

declare(strict_types=1);

namespace App\EventListener\Project;

use App\Event\PostConsumeEvent;
use App\Metrics\LastPush\LastPushManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: 10)]
readonly class ProjectLastPushCacherListener
{
    public function __construct(
        private LastPushManager $lastPushManager,
    ) {}

    public function __invoke(PostConsumeEvent $event): void
    {
        $this->lastPushManager->saveLastPush($event);
    }
}
