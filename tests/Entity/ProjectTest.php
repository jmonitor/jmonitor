<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testGetCurrentPlanReturnsFreeWhenSubscriptionIsExpired(): void
    {
        $project = new Project();
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setStartAt(new \DateTimeImmutable('-30 days'));
        $subscription->setEndAt(new \DateTimeImmutable('-1 day'));
        $subscription->setAutoRenew(false);
        $project->setSubscription($subscription);

        $this->assertSame(Plan::FREE, $project->getCurrentPlan());
    }

    public function testGetCurrentPlanReturnsProWhenTrialIsActive(): void
    {
        $project = new Project();
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setStartAt(new \DateTimeImmutable());
        $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
        $subscription->setAutoRenew(false);
        $project->setSubscription($subscription);

        $this->assertSame(Plan::PRO, $project->getCurrentPlan());
    }
}
