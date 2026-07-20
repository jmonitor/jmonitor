<?php

declare(strict_types=1);

namespace App\Command\Demo;

use App\Entity\Enums\Component;
use App\Entity\Enums\Plan;
use App\Entity\Enums\ProjectRole;
use App\Entity\Enums\UserStatus;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: self::COMMAND, description: 'Idempotently provision the public demo account and project')]
class DemoProvisionCommand
{
    public const string COMMAND = 'app:demo:provision';
    public const string EMAIL = Project::DEMO_EMAIL;
    public const string PASSWORD = 'demo';
    public const string PROJECT_NAME = Project::DEMO_NAME;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        // 1. Demo user (idempotent by email)
        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]) ?? new User();
        $user->setEmail(self::EMAIL);
        $user->setStatus(UserStatus::ACTIVE);
        // Only (re)hash when the stored hash doesn't already match. Hashers use a random salt,
        // so re-hashing on every deploy changes the stored hash. User::isEqualTo() compares the
        // password, so Symfony would treat the user as changed and log the demo user out at each deploy.
        if ($user->getPassword() === null || !$this->passwordHasher->isPasswordValid($user, self::PASSWORD)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
        }
        $this->em->persist($user);

        // 2. Demo project (idempotent: the project named PROJECT_NAME linked to the demo user)
        $project = $this->findDemoProject($user) ?? new Project();
        $project->setName(self::PROJECT_NAME);
        $project->setComponents(Component::cases());

        // 3. Active PRO subscription so time-series writes are allowed
        $subscription = $project->getSubscription() ?? new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setEndAt(new \DateTimeImmutable('+10 years'));
        $subscription->setAutoRenew(false);
        $project->setSubscription($subscription);
        $this->em->persist($subscription);
        $this->em->persist($project);

        // 4. VIEWER link (idempotent)
        $link = $this->findLink($user, $project) ?? new ProjectUser();
        $link->setUser($user);
        $link->setProject($project);
        $link->setRole(ProjectRole::VIEWER);
        $this->em->persist($link);

        $this->em->flush();

        $io->success('Demo account and project provisioned.');
        $io->writeln(sprintf('Login: %s / %s', self::EMAIL, self::PASSWORD));
        $io->writeln(sprintf('Project id: %d (resolved automatically by app:demo:run)', $project->getId()));

        return Command::SUCCESS;
    }

    private function findDemoProject(User $user): ?Project
    {
        foreach ($user->getProjectUsers() as $projectUser) {
            if ($projectUser->getProject()->getName() === self::PROJECT_NAME) {
                return $projectUser->getProject();
            }
        }

        return null;
    }

    private function findLink(User $user, Project $project): ?ProjectUser
    {
        foreach ($user->getProjectUsers() as $projectUser) {
            if ($projectUser->getProject() === $project) {
                return $projectUser;
            }
        }

        return null;
    }
}
