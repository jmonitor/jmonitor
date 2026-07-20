<?php

declare(strict_types=1);

namespace App\Alerting;

use App\Alerting\AlertChecker\AlertCheckerInterface;
use App\Entity\Alert;
use App\Event\AlertSpottedEvent;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class AlertChecker
{
    public function __construct(
        #[AutowireIterator('app.alert.checker')]
        private iterable $alertCheckers,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function check(Alert $alert, MetricBagDto $metricBag): void
    {
        /** @var AlertCheckerInterface $checker */
        foreach ($this->alertCheckers as $checker) {
            if ($checker->support($alert, $metricBag)) {
                $spottedAlert = $checker->check($alert, $metricBag);

                if ($spottedAlert) {
                    $this->eventDispatcher->dispatch(new AlertSpottedEvent($spottedAlert));
                }
            }
        }
    }
}
