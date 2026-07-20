<?php

declare(strict_types=1);

namespace App\Webhook\Stripe;

use App\Env;
use App\Event\StripeEvent;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Receives the Stripe webhook
 * (triggered in StripeRequestParser.php)
 *
 * Depending on the message, a regular Symfony event is dispatched
 *
 * Note that the message arriving here is async (in prod - see messenger.yaml)
 */
#[AsRemoteEventConsumer('stripe')]
final readonly class SripeWebhookListener implements ConsumerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function consume(RemoteEvent $event): void
    {
        if (!$event instanceof StripeRemoteEvent) {
            throw new \Exception('Unexpected event type, bug in StripeRequestParser?');
        }

        if (Env::isProd() && $this->alreadyTraited($event)) {
            $this->logger->warning('Webhook sent multiple times, skipped.', [
                'payload' => $event->getPayload(),
            ]);

            return;
        }

        $this->eventDispatcher->dispatch(new StripeEvent($event->stripeEvent));
    }

    /**
     * Stripe may send the same event multiple times
     * https://docs.stripe.com/webhooks#g%C3%A9rer-les-%C3%A9v%C3%A9nements-en-double
     */
    private function alreadyTraited(StripeRemoteEvent $event): bool
    {
        $item = $this->cache->getItem('stripe-event-' . md5($event->getId()));

        if ($item->isHit()) {
            return true;
        }

        $item->expiresAfter(24 * 60 * 60);
        $item->set(true);

        $this->cache->save($item);

        return false;
    }
}
