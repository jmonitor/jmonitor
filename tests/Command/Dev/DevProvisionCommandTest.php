<?php

declare(strict_types=1);

namespace App\Tests\Command\Dev;

use App\Command\Dev\DevProvisionCommand;
use App\Entity\Enums\Component;
use App\Entity\Enums\Plan;
use App\Entity\Enums\ProjectRole;
use App\Entity\Enums\Role;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DevProvisionCommandTest extends TestCase
{
    public function testRefusesToRunInProd(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        // The guard must prevent ANY write: a well-known OWNER account must never reach prod.
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy');

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository->expects($this->never())->method('findDemoProject');

        $command = new DevProvisionCommand(
            $em,
            $userRepository,
            $projectRepository,
            $this->createMock(UserPasswordHasherInterface::class),
            'prod',
        );

        self::assertSame(Command::FAILURE, $command($this->io()));
    }

    public function testProvisionsUserProjectSubscriptionAndOwnerLink(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-dev');

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $em->expects($this->once())->method('flush');

        $command = new DevProvisionCommand($em, $userRepository, $this->createMock(ProjectRepository::class), $hasher, 'dev');

        self::assertSame(Command::SUCCESS, $command($this->io()));

        $users = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof User));
        $projects = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof Project));
        $links = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof ProjectUser));

        self::assertCount(1, $users);
        self::assertSame(DevProvisionCommand::EMAIL, $users[0]->getEmail());
        self::assertSame('hashed-dev', $users[0]->getPassword());
        // Admin: the dev login must also open the EasyAdmin panel.
        self::assertSame(Role::ROLE_ADMIN, $users[0]->getRole());

        self::assertCount(1, $projects);
        $project = $projects[0];
        self::assertSame(DevProvisionCommand::PROJECT_NAME, $project->getName());
        // Fixed key: must override the random key from Project's constructor so the
        // committed JMONITOR_API_KEY in .env.dev matches out of the box.
        self::assertSame(DevProvisionCommand::API_KEY, $project->getApiKey());

        // Dev collector set (jmonitor.yaml when@dev): everything the dev stack runs.
        // Apache, Nginx and Postgres are excluded (the app is FrankenPHP/Caddy on MySQL).
        $components = $project->getComponents();
        self::assertContains(Component::System, $components);
        self::assertContains(Component::FrankenPHP, $components);
        self::assertNotContains(Component::Apache, $components);
        self::assertNotContains(Component::Nginx, $components);
        self::assertNotContains(Component::Postgres, $components);
        self::assertCount(count(Component::cases()) - 3, $components);

        // PRO subscription so the TIME_SERIES_CHART right allows InfluxDB writes.
        $subscription = $project->getSubscription();
        self::assertNotNull($subscription);
        self::assertSame(Plan::PRO, $subscription->getPlan());
        self::assertFalse($subscription->isAutoRenew());

        self::assertCount(1, $links);
        self::assertSame(ProjectRole::OWNER, $links[0]->getRole());
        self::assertSame($users[0], $links[0]->getUser());
        self::assertSame($project, $links[0]->getProject());
    }

    public function testExistingUserWithValidPasswordIsNotRehashed(): void
    {
        // Same rationale as the demo command: re-hashing on every provision changes the
        // stored hash (random salt) and User::isEqualTo() would drop the session.
        $existingHash = 'already-hashed-dev';
        $user = new User();
        $user->setEmail(DevProvisionCommand::EMAIL);
        $user->setPassword($existingHash);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')
            ->with($user, DevProvisionCommand::PASSWORD)
            ->willReturn(true);
        $hasher->expects($this->never())->method('hashPassword');

        $command = new DevProvisionCommand(
            $this->createMock(EntityManagerInterface::class),
            $userRepository,
            $this->createMock(ProjectRepository::class),
            $hasher,
            'dev',
        );
        $command($this->io());

        self::assertSame($existingHash, $user->getPassword());
    }

    public function testSecondRunReusesExistingProjectAndLink(): void
    {
        // Simulate a first provision already done: user linked OWNER to project "jmonitor".
        $user = new User();
        $user->setEmail(DevProvisionCommand::EMAIL);
        $user->setPassword('already-hashed-dev');

        $project = new Project();
        $project->setName(DevProvisionCommand::PROJECT_NAME);

        $link = new ProjectUser();
        $link->setProject($project);
        $link->setRole(ProjectRole::OWNER);
        // Wires both sides: adds to User::$projectUsers and calls $link->setUser($user).
        $user->addProjectUser($link);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(true);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $command = new DevProvisionCommand($em, $userRepository, $this->createMock(ProjectRepository::class), $hasher, 'dev');

        self::assertSame(Command::SUCCESS, $command($this->io()));

        // No duplicates: the SAME instances are re-persisted, no new Project/ProjectUser.
        $projects = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof Project));
        $links = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof ProjectUser));
        self::assertSame([$project], $projects);
        self::assertSame([$link], $links);
        self::assertSame(DevProvisionCommand::API_KEY, $project->getApiKey());
    }

    public function testLinksDevUserAsOwnerToDemoProjectWhenItExists(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-dev');

        // Realistic demo fixture: the demo user is linked (VIEWER), so isDemo() holds.
        $demoProject = new Project();
        $demoProject->setName(Project::DEMO_NAME);
        $demoUser = new User();
        $demoUser->setEmail(Project::DEMO_EMAIL);
        $demoUserLink = new ProjectUser();
        $demoUserLink->setUser($demoUser);
        $demoUserLink->setRole(ProjectRole::VIEWER);
        $demoProject->addProjectUser($demoUserLink);
        self::assertTrue($demoProject->isDemo(), 'fixture sanity: a real demo project before linking dev');

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository->method('findDemoProject')
            ->with(Project::DEMO_EMAIL, Project::DEMO_NAME)
            ->willReturn($demoProject);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $command = new DevProvisionCommand($em, $userRepository, $projectRepository, $hasher, 'dev');

        self::assertSame(Command::SUCCESS, $command($this->io()));

        $demoLinks = array_values(array_filter(
            $persisted,
            fn(object $e): bool => $e instanceof ProjectUser && $e->getProject() === $demoProject,
        ));
        self::assertCount(1, $demoLinks);
        self::assertSame(ProjectRole::OWNER, $demoLinks[0]->getRole());
        self::assertSame(DevProvisionCommand::EMAIL, $demoLinks[0]->getUser()->getEmail());

        // Adding the dev user as an extra OWNER must not turn the demo project into a
        // non-demo project — otherwise demo side-effect suppression would reactivate.
        self::assertTrue($demoProject->isDemo(), 'linking dev as OWNER must not break isDemo()');
    }

    public function testDemoLinkReusesExistingLinkOnSecondRun(): void
    {
        $user = new User();
        $user->setEmail(DevProvisionCommand::EMAIL);
        $user->setPassword('already-hashed-dev');

        $demoProject = new Project();
        $demoProject->setName(Project::DEMO_NAME);

        // Dev user already OWNER of the demo project from a previous provision.
        $existingDemoLink = new ProjectUser();
        $existingDemoLink->setProject($demoProject);
        $existingDemoLink->setRole(ProjectRole::OWNER);
        $user->addProjectUser($existingDemoLink);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(true);

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository->method('findDemoProject')->willReturn($demoProject);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $command = new DevProvisionCommand($em, $userRepository, $projectRepository, $hasher, 'dev');

        self::assertSame(Command::SUCCESS, $command($this->io()));

        // The existing demo link is reused (same instance re-persisted), no duplicate created.
        $demoLinks = array_values(array_filter(
            $persisted,
            fn(object $e): bool => $e instanceof ProjectUser && $e->getProject() === $demoProject,
        ));
        self::assertSame([$existingDemoLink], $demoLinks);
    }

    public function testSkipsDemoLinkWhenDemoProjectMissing(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-dev');

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository->method('findDemoProject')->willReturn(null);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $command = new DevProvisionCommand($em, $userRepository, $projectRepository, $hasher, 'dev');

        self::assertSame(Command::SUCCESS, $command($this->io()));

        // Only the dev project's OWNER link — no demo link when the demo project is absent.
        $links = array_values(array_filter($persisted, fn(object $e): bool => $e instanceof ProjectUser));
        self::assertCount(1, $links);
    }

    private function io(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), new NullOutput());
    }
}
