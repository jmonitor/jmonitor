<?php

declare(strict_types=1);

namespace App\Tests\Subscription;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Subscription\TrialSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TrialSubscriptionServiceTest extends TestCase
{
    public function testGrantsTrialOnNewProject(): void
    {
        $project = new Project();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new TrialSubscriptionService($em);
        $service->grantTrial($project);

        $subscription = $project->getSubscription();

        $this->assertNotNull($subscription);
        $this->assertSame(Plan::PRO, $subscription->getPlan());
        $this->assertSame(false, $subscription->isAutoRenew());
        $this->assertNull($subscription->getStripeSubscriptionId());

        $expectedEnd = new \DateTimeImmutable('+7 days');
        $this->assertEqualsWithDelta(
            $expectedEnd->getTimestamp(),
            $subscription->getEndAt()->getTimestamp(),
            5,
        );
    }
}
