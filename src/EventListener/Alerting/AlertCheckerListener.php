<?php

declare(strict_types=1);

namespace App\EventListener\Alerting;

use App\Event\PostConsumeEvent;
use App\Message\CheckAlertMessage;
use App\Security\Voter\Right\Right;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class AlertCheckerListener
{
    public function __construct(
        private Security $security,
        private MessageBusInterface $bus,
    ) {}

    public function __invoke(PostConsumeEvent $event): void
    {
        if (!$this->security->isGranted(Right::ALERTING->value, $event->project)) {
            return;
        }

        // the demo project is fed with synthetic metrics: no alert check is run
        // on that fake data (so neither pause nor notification)
        if ($event->project->isDemo()) {
            return;
        }

        $rawBags = [];

        foreach ($event->metricBags as $consumer => $bag) {
            $rawBags[] = [
                'consumer' => $consumer,
                'metrics' => $bag->all(),
                'receivedAt' => $bag->getReceivedAt(),
                'version' => $bag->getVersion(),
                'threw' => $bag->hasThrew(),
            ];
        }

        $this->bus->dispatch(new CheckAlertMessage($event->project->getId(), $rawBags));
    }
}
