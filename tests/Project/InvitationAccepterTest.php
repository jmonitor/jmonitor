<?php

declare(strict_types=1);

namespace App\Tests\Project;

use App\Entity\Enums\ProjectRole;
use App\Entity\Enums\UserStatus;
use App\Entity\Project;
use App\Entity\ProjectInvitation;
use App\Entity\User;
use App\Project\InvitationAccepter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class InvitationAccepterTest extends TestCase
{
    public function testCreatesMembershipCarryingTheInvitedRole(): void
    {
        $project = new Project();
        $invitation = new ProjectInvitation();
        $invitation->setProject($project);
        $invitation->setRole(ProjectRole::ADMIN);
        $user = new User();

        $projectUser = new InvitationAccepter($this->em())->accept($invitation, $user);

        $this->assertSame($project, $projectUser->getProject());
        $this->assertSame($user, $projectUser->getUser());
        $this->assertSame(ProjectRole::ADMIN, $projectUser->getRole());
    }

    public function testActivatesTheUser(): void
    {
        $user = new User();

        $this->assertSame(UserStatus::ONBOARDING, $user->getStatus());

        new InvitationAccepter($this->em())->accept($this->invitation(), $user);

        $this->assertSame(UserStatus::ACTIVE, $user->getStatus());
    }

    public function testConsumesTheInvitationInASingleFlush(): void
    {
        $invitation = $this->invitation();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('remove')->with($invitation);
        $em->expects($this->once())->method('flush');

        new InvitationAccepter($em)->accept($invitation, new User());
    }

    private function invitation(): ProjectInvitation
    {
        return new ProjectInvitation()
            ->setProject(new Project())
            ->setRole(ProjectRole::VIEWER)
        ;
    }

    private function em(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }
}
