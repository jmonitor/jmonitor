<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Alerting\AlertChecker;
use App\Entity\Project;
use App\Message\CheckAlertMessage;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckAlertMessageHandler
{
    public function __construct(
        private AlertChecker $alertChecker,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CheckAlertMessage $message): void
    {
        $project = $this->em->find(Project::class, $message->projectId);

        if (!$project) {
            return;
        }

        foreach ($message->bags as $bag) {
            $bag = MetricBagDto::create(
                $project,
                Consumer::from($bag['consumer']),
                $bag['version'],
                $bag['metrics'],
                $bag['receivedAt'],
                $bag['threw'],
            );

            foreach ($project->getAlerts() as $alert) {
                if ($alert->isPaused()) {
                    continue;
                }

                // pre-filter: if the alert only concerns one component
                // and the bag is not a bag of that component, skip it
                // -> could also be done per consumer, to be seen
                if ($alert->getAlertMetric()->component() !== $bag->getConsumer()->getComponent()) {
                    continue;
                }

                $this->alertChecker->check($alert, $bag);
            }
        }
    }
}
