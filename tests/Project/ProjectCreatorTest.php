<?php

declare(strict_types=1);

namespace App\Tests\Project;

use App\Entity\Enums\Plan;
use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\User;
use App\Plan\Edition;
use App\Project\ProjectCreator;
use App\Subscription\TrialSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProjectCreatorTest extends TestCase
{
    public function testCreatesProjectWithOwnerAndTrialInCloud(): void
    {
        $project = new Project();
        $user = new User();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->once())->method('flush');

        $creator = new ProjectCreator($em, new TrialSubscriptionService($em), Edition::CLOUD);
        $creator->create($project, $user);

        $projectUsers = $project->getProjectUsers();
        $this->assertCount(1, $projectUsers);

        $projectUser = $projectUsers[0];
        $this->assertSame($user, $projectUser->getUser());
        $this->assertSame(ProjectRole::OWNER, $projectUser->getRole());

        $subscription = $project->getSubscription();
        $this->assertNotNull($subscription);
        $this->assertSame(Plan::PRO, $subscription->getPlan());
    }

    public function testDoesNotGrantTrialInSelfHosted(): void
    {
        $project = new Project();
        $user = new User();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->once())->method('flush');

        $creator = new ProjectCreator($em, new TrialSubscriptionService($em), Edition::SELF_HOSTED);
        $creator->create($project, $user);

        $this->assertCount(1, $project->getProjectUsers());
        $this->assertNull($project->getSubscription());
    }
}
