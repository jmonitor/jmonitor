<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MetricsReceivedMessage;
use App\Metrics\Consumer;
use App\Repository\ProjectRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class MetricsReceivedMessageHandler
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private Consumer $consumer,
    ) {}

    public function __invoke(MetricsReceivedMessage $message): void
    {
        $project = $this->projectRepository->find($message->getProjectId());

        if ($project) {
            $this->consumer->consume($message->getMetrics(), $project, $message->getReceivedAt(), $message->getJmonitorVersion());
        }
    }
}
