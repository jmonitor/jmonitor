<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use App\Security\Voter\PlanVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PlanVoterTest extends TestCase
{
    public function testGrantsWhenResolvedPlanScoreIsSufficient(): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(resolvedPlan: Plan::PRO, attribute: Plan::PRO->value));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(resolvedPlan: Plan::SELF_HOSTED, attribute: Plan::MAX->value));
    }

    public function testDeniesWhenResolvedPlanScoreIsTooLow(): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote(resolvedPlan: Plan::FREE, attribute: Plan::PRO->value));
    }

    private function vote(Plan $resolvedPlan, string $attribute): int
    {
        $project = new Project();

        if ($resolvedPlan === Plan::SELF_HOSTED) {
            $resolver = new PlanResolver(Edition::SELF_HOSTED);
        } else {
            $resolver = new PlanResolver(Edition::CLOUD);

            if ($resolvedPlan !== Plan::FREE) {
                $subscription = new Subscription();
                $subscription->setPlan($resolvedPlan);
                $subscription->setStartAt(new \DateTimeImmutable());
                $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
                $subscription->setAutoRenew(false);
                $project->setSubscription($subscription);
            }
        }

        $voter = new PlanVoter($this->createStub(ProjectContext::class), $resolver);

        return $voter->vote($this->createStub(TokenInterface::class), $project, [$attribute]);
    }
}
