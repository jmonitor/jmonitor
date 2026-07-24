<?php

declare(strict_types=1);

namespace App\EventListener\Metric;

use App\Bridge\Mercure\MercureTopicProvider;
use App\Event\PostConsumeEvent;
use App\Security\Voter\Right\Right;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsEventListener]
readonly class AutoRefreshListener
{
    public function __construct(
        private HubInterface $hub,
        private MercureTopicProvider $mercureTopicProvider,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(PostConsumeEvent $event): void
    {
        if (!$this->security->isGranted(Right::AUTOREFRESH->value, $event->project)) {
            return;
        }

        if (!$this->hub->getPublicUrl()) {
            return;
        }

        $components = $event->getComponents();

        try {
            // Project-wide topic for authenticated dashboards: one message listing every
            // updated component, filtered client-side.
            $this->hub->publish(
                new Update(
                    $this->mercureTopicProvider->getConsumedMetricUrl($event->project),
                    json_encode(['components' => $components]),
                ),
            );

            // Per-component sub-topics for public embeds: each carries only its own
            // component, so a public subscriber never sees the rest of the project.
            foreach ($components as $component) {
                $this->hub->publish(
                    new Update(
                        $this->mercureTopicProvider->getConsumedMetricComponentUrl($event->project, $component),
                        json_encode(['components' => [$component]]),
                    ),
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('Autorefresh error', [
                'exception' => $e,
                'project' => $event->project->getName(),
            ]);
        }
    }
}
