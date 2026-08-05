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

        $this->show($embed);
    }

    public function testFreePlanProjectContentIs404(): void
    {
        $embed = new Embed()->setProject($this->projectWithPlan(Plan::FREE));

        $this->expectException(NotFoundHttpException::class);

        $this->content($embed);
    }

    public function testStoredConfigWithAnUnknownMetricIs404(): void
    {
        $embed = new Embed()->setProject($this->projectWithPlan(Plan::PRO));
        // Simulates a published embed whose metric was later removed from the Metric enum.
        new \ReflectionProperty(Embed::class, 'config')->setValue($embed, ['m' => 'does.not_exist']);

        $this->expectException(NotFoundHttpException::class);

        $this->show($embed);
    }

    private function show(Embed $embed): void
    {
        new PublicEmbedController()->show(
            $embed,
            $this->projectContext(),
            new PlanResolver(Edition::CLOUD),
            $this->createMock(EntityManagerInterface::class),
            new MockClock(),
        );
    }

    private function content(Embed $embed): void
    {
        new PublicEmbedController()->content(
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
