<?php

declare(strict_types=1);

namespace App\EventListener\Stripe;

use Stripe\Event;
use App\Bridge\InfluxDb\InfluxDb;
use App\Bridge\Stripe\StripeEventType;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Event\StripeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Creates the subscription
 */
#[AsEventListener]
readonly class SubscriptionListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private InfluxDb $influxDb,
    ) {}

    public function __invoke(StripeEvent $e): void
    {
        $object = $e->stripeEvent->data->object;

        if (!$object instanceof StripeSubscription) {
            return;
        }

        match ($e->type) {
            StripeEventType::CUSTOMER_SUBSCRIPTION_CREATED => $this->onSubscriptionCreated($object),
            StripeEventType::CUSTOMER_SUBSCRIPTION_UPDATED => $this->onSubscriptionUpdated($e->stripeEvent),
            StripeEventType::CUSTOMER_SUBSCRIPTION_DELETED => $this->onSubscriptionDeleted($object),
            default => null,
        };
    }

    private function onSubscriptionCreated(StripeSubscription $stripeSubscription): void
    {
        // just in case, duplicate webhook issue
        $exist = $this->em->getRepository(Subscription::class)->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if ($exist) {
            return;
        }

        $projectId = $stripeSubscription->metadata['project_id'];
        $plan = Plan::from($stripeSubscription->plan->metadata['jmonitor_plan']); // @phpstan-ignore-line
        $project = $this->em->find(Project::class, $projectId);

        /** @var SubscriptionItem $item */
        $item = $stripeSubscription->items->first();

        $subscription = new Subscription();
        $subscription->setProject($project);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId($stripeSubscription->id);
        $subscription->setStartAt(new \DateTimeImmutable());

        if ($stripeSubscription->cancel_at) {
            $subscription->setEndAt(new \DateTimeImmutable('@' . $stripeSubscription->cancel_at)->setTimezone(new \DateTimeZone('Europe/Paris')));
            $subscription->setAutoRenew(false);
        } else {
            $subscription->setEndAt(new \DateTimeImmutable('@' . $item->current_period_end)->setTimezone(new \DateTimeZone('Europe/Paris')));
            $subscription->setAutoRenew(!$stripeSubscription->cancel_at_period_end);
        }

        $project->setSubscription($subscription);

        // the bucket may have been created earlier with the trial plan's retention
        if ($project?->getBucketId() && $plan !== Plan::FREE) {
            $this->influxDb->updateBucketRetention($project->getBucketId(), $plan->influxDataRetentionSecond());
        }

        $this->em->persist($subscription);
        $this->em->flush();
    }

    private function onSubscriptionUpdated(Event $stripeEvent): void
    {
        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = $stripeEvent->data->object;
        /** @var SubscriptionItem $item */
        $item = $stripeSubscription->items->first();

        $previousAttributes = $stripeEvent->data->previous_attributes->toArray(); // @phpstan-ignore-line

        $subscription = $this->em->getRepository(Subscription::class)->findOneBy([
            'stripeSubscriptionId' => $stripeSubscription->id,
        ]);

        if (!$subscription) {
            throw new \RuntimeException('Subscription not found ' . $stripeSubscription->id);
        }

        $updateHandled = false;

        // cancellation (or resumption) of auto renewal
        if (array_key_exists('cancel_at', $previousAttributes)) {
            if ($stripeSubscription->cancel_at) {
                $subscription->setEndAt(new \DateTimeImmutable('@' . $stripeSubscription->cancel_at)->setTimezone(new \DateTimeZone('Europe/Paris')));
                $subscription->setAutoRenew(false);
            } else {
                $subscription->setEndAt(new \DateTimeImmutable('@' . $item->current_period_end)->setTimezone(new \DateTimeZone('Europe/Paris')));
                $subscription->setAutoRenew(!$stripeSubscription->cancel_at_period_end);
            }

            $updateHandled = true;
        }

        // the plan has changed, presumably via the Stripe portal
        if (array_key_exists('plan', $previousAttributes)) {
            $newPlan = Plan::from($stripeSubscription->plan->metadata['jmonitor_plan']); // @phpstan-ignore-line
            $subscription->setPlan($newPlan);

            // adjust the InfluxDB bucket retention to the new plan (e.g. max -> pro = 6 months -> 2 months)
            $project = $subscription->getProject();
            if ($project?->getBucketId() && $newPlan !== Plan::FREE) {
                $this->influxDb->updateBucketRetention($project->getBucketId(), $newPlan->influxDataRetentionSecond());
            }

            $updateHandled = true;
        }

        // automatic Stripe payment, i.e. the auto renewal
        if (
            array_key_exists('items', $previousAttributes)
            && array_key_exists('latest_invoice', $previousAttributes)
        ) {
            $subscription->setStartAt(new \DateTimeImmutable('@' . $item->current_period_start)->setTimezone(new \DateTimeZone('Europe/Paris')));
            $subscription->setEndAt(new \DateTimeImmutable('@' . $item->current_period_end)->setTimezone(new \DateTimeZone('Europe/Paris')));
            $updateHandled = true;
        }

        if (!$updateHandled) {
            // update webhook without an actual update, log it
            $this->logger->error('Subscription update not handled', [
                'stripeEvent' => $stripeEvent->toArray(),
            ]);

            return;
        }

        $this->em->flush();
    }

    private function onSubscriptionDeleted(StripeSubscription $stripeSubscription): void
    {
        $subscription = $this->em->getRepository(Subscription::class)->findOneBy([
            'stripeSubscriptionId' => $stripeSubscription->id,
        ]);

        if (!$subscription) {
            return;
        }

        // The subscription is not deleted right away: it is kept during the grace period
        // so that the purge (PurgeExpiredSubscriptionsCommand) deletes the bucket and then the
        // subscription. The real end date returned by Stripe is stored (not a made-up one).
        $subscription->setAutoRenew(false);

        if ($stripeSubscription->ended_at) {
            $subscription->setEndAt(new \DateTimeImmutable('@' . $stripeSubscription->ended_at)->setTimezone(new \DateTimeZone('Europe/Paris')));
        }

        $this->em->flush();
    }
}
