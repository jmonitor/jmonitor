<?php

declare(strict_types=1);

namespace App\Tests\Plan;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use PHPUnit\Framework\TestCase;

class PlanResolverTest extends TestCase
{
    public function testCloudEditionDelegatesToSubscription(): void
    {
        $project = new Project();
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setStartAt(new \DateTimeImmutable());
        $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
        $subscription->setAutoRenew(false);
        $project->setSubscription($subscription);

        $resolver = new PlanResolver(Edition::CLOUD);

        $this->assertSame(Plan::PRO, $resolver->resolve($project));
        $this->assertSame(Plan::FREE, $resolver->resolve(new Project()));
    }

    public function testSelfHostedEditionAlwaysReturnsSelfHosted(): void
    {
        $resolver = new PlanResolver(Edition::SELF_HOSTED);

        $this->assertSame(Plan::SELF_HOSTED, $resolver->resolve(new Project()));
    }
}
