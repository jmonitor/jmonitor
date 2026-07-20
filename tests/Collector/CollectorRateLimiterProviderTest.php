<?php

declare(strict_types=1);

namespace App\Tests\Collector;

use App\Collector\CollectorRateLimiterProvider;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class CollectorRateLimiterProviderTest extends TestCase
{
    public function testReturnsFactoryMatchingResolvedPlan(): void
    {
        $free = $this->createStub(RateLimiterFactoryInterface::class);
        $pro = $this->createStub(RateLimiterFactoryInterface::class);
        $max = $this->createStub(RateLimiterFactoryInterface::class);
        $selfHosted = $this->createStub(RateLimiterFactoryInterface::class);

        $expectations = [
            [Plan::FREE, $free],
            [Plan::PRO, $pro],
            [Plan::MAX, $max],
            [Plan::SELF_HOSTED, $selfHosted],
        ];

        foreach ($expectations as [$plan, $expectedFactory]) {
            $project = new Project();

            if ($plan === Plan::SELF_HOSTED) {
                $resolver = new PlanResolver(Edition::SELF_HOSTED);
            } else {
                $resolver = new PlanResolver(Edition::CLOUD);

                if ($plan !== Plan::FREE) {
                    $subscription = new Subscription();
                    $subscription->setPlan($plan);
                    $subscription->setStartAt(new \DateTimeImmutable());
                    $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
                    $subscription->setAutoRenew(false);
                    $project->setSubscription($subscription);
                }
            }

            $provider = new CollectorRateLimiterProvider($free, $pro, $max, $selfHosted, $resolver);

            $this->assertSame($expectedFactory, $provider->getRateLimiterFactory($project), 'Plan ' . $plan->value);
        }
    }
}
