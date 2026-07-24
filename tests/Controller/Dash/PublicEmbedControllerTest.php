<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash;

use App\Controller\Dash\PublicEmbedController;
use App\Entity\Embed;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicEmbedControllerTest extends TestCase
{
    public function testFreePlanProjectIs404(): void
    {
        $embed = new Embed()->setProject($this->projectWithPlan(Plan::FREE));

        $this->expectException(NotFoundHttpException::class);

        $this->invoke($embed);
    }

    private function invoke(Embed $embed): void
    {
        new PublicEmbedController()->__invoke(
            $embed,
            $this->projectContext(),
            new PlanResolver(Edition::CLOUD),
            $this->createMock(EntityManagerInterface::class),
            new MockClock(),
        );
    }

    private function projectWithPlan(Plan $plan): Project
    {
        $project = new Project();

        if ($plan !== Plan::FREE) {
            $subscription = new Subscription();
            $subscription->setPlan($plan);
            $subscription->setStartAt(new \DateTimeImmutable());
            $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
            $subscription->setAutoRenew(false);
            $project->setSubscription($subscription);
        }

        return $project;
    }

    private function projectContext(): ProjectContext
    {
        return new ProjectContext(
            new RequestStack(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Security::class),
        );
    }
}
