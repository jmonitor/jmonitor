<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Stripe;

use App\Bridge\InfluxDb\InfluxDb;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Event\StripeEvent;
use App\EventListener\Stripe\SubscriptionListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\Event;

class SubscriptionListenerTest extends TestCase
{
    private const PERIOD_START = 1750000000;
    private const PERIOD_END = 1760000000;
    private const CANCEL_AT = 1755000000;

    public function testPlanChangeUpdatesPlanAndBucketRetention(): void
    {
        $project = new Project();
        $project->setBucketId('bucket-1');

        $subscription = $this->subscription(Plan::PRO, $project);

        $retentionCalls = [];
        $influxDb = new InfluxDbSpy(function (string $bucketId, int $retentionDuration) use (&$retentionCalls): void {
            $retentionCalls[] = [$bucketId, $retentionDuration];
        });

        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 1), $this->createStub(LoggerInterface::class), $influxDb);
        $listener($this->updateEvent(previousAttributes: ['plan' => ['id' => 'price_old']], plan: 'max'));

        $this->assertSame(Plan::MAX, $subscription->getPlan());
        $this->assertSame([['bucket-1', Plan::MAX->influxDataRetentionSecond()]], $retentionCalls);
    }

    public function testPlanChangeWithoutBucketDoesNotTouchInflux(): void
    {
        $subscription = $this->subscription(Plan::PRO, new Project());

        // the spy throws on any call to updateBucketRetention()
        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 1), $this->createStub(LoggerInterface::class), new InfluxDbSpy());
        $listener($this->updateEvent(previousAttributes: ['plan' => ['id' => 'price_old']], plan: 'max'));

        $this->assertSame(Plan::MAX, $subscription->getPlan());
    }

    public function testCreationSyncsBucketRetentionToNewPlan(): void
    {
        $project = new Project();
        $project->setBucketId('bucket-1');

        $retentionCalls = [];
        $influxDb = new InfluxDbSpy(function (string $bucketId, int $retentionDuration) use (&$retentionCalls): void {
            $retentionCalls[] = [$bucketId, $retentionDuration];
        });

        $listener = new SubscriptionListener($this->creationEntityManager($project), $this->createStub(LoggerInterface::class), $influxDb);
        $listener($this->creationEvent($project));

        $this->assertSame([['bucket-1', Plan::MAX->influxDataRetentionSecond()]], $retentionCalls);
    }

    public function testCreationWithoutBucketDoesNotTouchInflux(): void
    {
        $project = new Project();

        // the spy throws on any call to updateBucketRetention()
        $listener = new SubscriptionListener($this->creationEntityManager($project), $this->createStub(LoggerInterface::class), new InfluxDbSpy());
        $listener($this->creationEvent($project));

        $this->assertInstanceOf(Subscription::class, $project->getSubscription());
    }

    public function testCancellationDisablesAutoRenewAndSetsEndAt(): void
    {
        $subscription = $this->subscription(Plan::PRO, new Project());

        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 1), $this->createStub(LoggerInterface::class), new InfluxDbSpy());
        $listener($this->updateEvent(previousAttributes: ['cancel_at' => null], cancelAt: self::CANCEL_AT));

        $this->assertFalse($subscription->isAutoRenew());
        $this->assertSame(self::CANCEL_AT, $subscription->getEndAt()?->getTimestamp());
    }

    public function testCancellationRevertRestoresAutoRenewAndPeriodEnd(): void
    {
        $subscription = $this->subscription(Plan::PRO, new Project());

        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 1), $this->createStub(LoggerInterface::class), new InfluxDbSpy());
        $listener($this->updateEvent(previousAttributes: ['cancel_at' => self::CANCEL_AT]));

        $this->assertTrue($subscription->isAutoRenew());
        $this->assertSame(self::PERIOD_END, $subscription->getEndAt()?->getTimestamp());
    }

    public function testAutoRenewalPersistsNewPeriod(): void
    {
        $subscription = $this->subscription(Plan::PRO, new Project());

        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 1), $this->createStub(LoggerInterface::class), new InfluxDbSpy());
        $listener($this->updateEvent(previousAttributes: [
            'items' => ['data' => []],
            'latest_invoice' => 'in_123',
        ]));

        $this->assertSame(self::PERIOD_START, $subscription->getStartAt()?->getTimestamp());
        $this->assertSame(self::PERIOD_END, $subscription->getEndAt()?->getTimestamp());
    }

    public function testUnhandledUpdateIsLoggedAndNotFlushed(): void
    {
        $subscription = $this->subscription(Plan::PRO, new Project());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $listener = new SubscriptionListener($this->entityManager($subscription, expectedFlushes: 0), $logger, new InfluxDbSpy());
        $listener($this->updateEvent(previousAttributes: ['metadata' => ['foo' => 'bar']]));
    }

    private function subscription(Plan $plan, Project $project): Subscription
    {
        $subscription = new Subscription();
        $subscription->setPlan($plan);
        $subscription->setProject($project);
        $subscription->setAutoRenew(true);
        $subscription->setStartAt(new \DateTimeImmutable('@' . (self::PERIOD_START - 100000)));
        $subscription->setEndAt(new \DateTimeImmutable('@' . (self::PERIOD_END - 100000)));

        return $subscription;
    }

    private function entityManager(Subscription $subscription, int $expectedFlushes): EntityManagerInterface&MockObject
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($subscription);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->exactly($expectedFlushes))->method('flush');

        return $em;
    }

    private function creationEntityManager(Project $project): EntityManagerInterface&MockObject
    {
        $repository = $this->createMock(EntityRepository::class);
        // no existing subscription for this stripe id, so the duplicate check doesn't early-return
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('find')->willReturn($project);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        return $em;
    }

    private function creationEvent(Project $project): StripeEvent
    {
        return new StripeEvent(Event::constructFrom([
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'object' => 'subscription',
                    'id' => 'sub_new_123',
                    'cancel_at' => null,
                    'cancel_at_period_end' => false,
                    'metadata' => ['project_id' => '1'],
                    'plan' => ['object' => 'plan', 'metadata' => ['jmonitor_plan' => 'max']],
                    'items' => [
                        'object' => 'list',
                        'data' => [[
                            'object' => 'subscription_item',
                            'current_period_start' => self::PERIOD_START,
                            'current_period_end' => self::PERIOD_END,
                        ]],
                    ],
                ],
            ],
        ]));
    }

    private function updateEvent(array $previousAttributes, string $plan = 'pro', ?int $cancelAt = null, bool $cancelAtPeriodEnd = false): StripeEvent
    {
        return new StripeEvent(Event::constructFrom([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'object' => 'subscription',
                    'id' => 'sub_123',
                    'cancel_at' => $cancelAt,
                    'cancel_at_period_end' => $cancelAtPeriodEnd,
                    'plan' => ['object' => 'plan', 'metadata' => ['jmonitor_plan' => $plan]],
                    'items' => [
                        'object' => 'list',
                        'data' => [[
                            'object' => 'subscription_item',
                            'current_period_start' => self::PERIOD_START,
                            'current_period_end' => self::PERIOD_END,
                        ]],
                    ],
                ],
                'previous_attributes' => $previousAttributes,
            ],
        ]));
    }
}

/**
 * Spy implementation of InfluxDb to record method calls.
 */
final class InfluxDbSpy extends InfluxDb
{
    /**
     * @param \Closure(string, int): void $onUpdateBucketRetention
     */
    public function __construct(private ?\Closure $onUpdateBucketRetention = null) {}

    public function updateBucketRetention(string $bucketId, int $retentionDuration): void
    {
        if ($this->onUpdateBucketRetention === null) {
            throw new \LogicException('Unexpected call to updateBucketRetention()');
        }

        ($this->onUpdateBucketRetention)($bucketId, $retentionDuration);
    }
}
