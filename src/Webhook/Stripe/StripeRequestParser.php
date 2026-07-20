<?php

declare(strict_types=1);

namespace App\Webhook\Stripe;

use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IpsRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

/**
 * https://docs.stripe.com/webhooks
 * https://docs.stripe.com/ips#webhook-notifications
 */
final class StripeRequestParser extends AbstractRequestParser
{
    /**
     * Allowed IPs
     * https://docs.stripe.com/ips#webhook-notifications
     */
    private const array STRIPE_IPS = [
        '3.18.12.63',
        '3.130.192.231',
        '13.235.14.237',
        '13.235.122.149',
        '18.211.135.69',
        '35.154.171.200',
        '52.15.183.38',
        '54.88.130.119',
        '54.88.130.237',
        '54.187.174.169',
        '54.187.205.235',
        '54.187.216.72',
        '127.0.0.1', // for dev
        '10.0.0.0/8', // Private network
        '172.16.0.0/12', // Private network
        '192.168.0.0/16', // Private network
    ];

    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new IpsRequestMatcher(self::STRIPE_IPS),
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent
    {
        $payload = $request->getPayload()->all();

        $this->logger->info('Stripe webhook triggered', [
            'payload' => $payload,
            'headers' => $request->headers->all(),
        ]);

        try {
            if ($secret) {
                $event = Webhook::constructEvent($request->getContent(), $request->headers->get('stripe-signature'), $secret);
            } else {
                // in dev, no signature check
                $event = Event::constructFrom($payload);
            }
        } catch (\UnexpectedValueException $e) {
            $this->logger->warning('Stripe : Invalid payload.', [
                'payload' => $payload,
                'exception' => $e,
            ]);

            throw new RejectWebhookException(406, 'Invalid payload', $e);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe : Invalid signature', [
                'payload' => $payload,
                'sig_header' => $e->getSigHeader(),
                'exception' => $e,
            ]);

            throw new RejectWebhookException(406, 'Invalid signature', $e);
        }

        // "already processed" events are handled in the consumer, not here
        // (so we can return a 200 to Stripe and avoid retries)
        return new StripeRemoteEvent($event);
    }
}
