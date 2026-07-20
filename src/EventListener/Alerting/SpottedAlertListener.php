<?php

declare(strict_types=1);

namespace App\EventListener\Alerting;

use App\Alerting\AlertNotifier;
use App\Event\AlertSpottedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class SpottedAlertListener
{
    public function __construct(
        private AlertNotifier $alertNotifier,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(AlertSpottedEvent $event): void
    {
        $event->getSpottedAlert()->getAlert()->setPaused(true);
        $this->em->flush();

        $this->alertNotifier->notify($event->getSpottedAlert());
    }
}
