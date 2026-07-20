<?php

declare(strict_types=1);

namespace App\Command\Dev;

use App\Entity\Enums\Component;
use App\Entity\Enums\Plan;
use App\Entity\Enums\ProjectRole;
use App\Entity\Enums\Role;
use App\Entity\Enums\UserStatus;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Deliberate mirror of DemoProvisionCommand (no shared abstraction): the demo command runs
 * on prod deploys and must not change; this one is dev-only tooling. The dev project is a
 * NORMAL project (alerts and notifications stay active — that is the point), so nothing
 * else in the codebase needs to recognize it.
 */
#[AsCommand(name: self::COMMAND, description: 'Idempotently provision the dev self-monitoring account and project')]
class DevProvisionCommand
{
    public const string COMMAND = 'app:dev:provision';
    public const string EMAIL = 'dev@jmonitor.io';
    public const string PASSWORD = 'dev';
    public const string PROJECT_NAME = 'jmonitor';
    /** Matches the committed JMONITOR_API_KEY default in .env.dev, so `make monitor` works with zero manual steps. */
    public const string API_KEY = 'dev-selfmonitoring-key';

    /**
     * The dev collector set (config/packages/jmonitor.yaml when@dev): everything the dev stack runs.
     */
    private const array COMPONENTS = [
        Component::System,
        Component::PHP,
        Component::Caddy,
        Component::FrankenPHP,
        Component::MySQL,
        Component::Redis,
        Component::Symfony,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        // An OWNER account with password "dev" and a well-known API key must never exist in prod.
        if ($this->environment === 'prod') {
            $io->error('app:dev:provision creates a well-known dev account and must not run in prod.');

            return Command::FAILURE;
        }

        // 1. Dev user (idempotent by email)
        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]) ?? new User();
        $user->setEmail(self::EMAIL);
        $user->setStatus(UserStatus::ACTIVE);
        // Admin so the dev login also reaches the EasyAdmin panel (admin.jmonitor.localhost).
        $user->setRole(Role::ROLE_ADMIN);
        // Only (re)hash when the stored hash doesn't already match, same reason as the demo
        // command: hashers use a random salt, and User::isEqualTo() compares the password,
        // so re-hashing on every provision would log the dev user out.
        if ($user->getPassword() === null || !$this->passwordHasher->isPasswordValid($user, self::PASSWORD)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
        }
        $this->em->persist($user);

        // 2. Dev project (idempotent: the project named PROJECT_NAME linked to the dev user).
        // No bucket creation here: Consumer creates it lazily on the first metrics push.
        $project = $this->findDevProject($user) ?? new Project();
        $project->setName(self::PROJECT_NAME);
        $project->setApiKey(self::API_KEY);
        $project->setComponents(self::COMPONENTS);

        // 3. Active PRO subscription so time-series writes are allowed
        $subscription = $project->getSubscription() ?? new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setEndAt(new \DateTimeImmutable('+10 years'));
        $subscription->setAutoRenew(false);
        $project->setSubscription($subscription);
        $this->em->persist($subscription);
        $this->em->persist($project);

        // 4. OWNER link (idempotent)
        $link = $this->findLink($user, $project) ?? new ProjectUser();
        $link->setUser($user);
        $link->setProject($project);
        $link->setRole(ProjectRole::OWNER);
        $this->em->persist($link);

        // 5. Best-effort OWNER link to the demo project (optional: it only exists once
        // `app:demo:provision` / `make demo` has run). A single dev login then sees both the
        // real `jmonitor` project and the demo project — rich synthetic data for every
        // component, including the ones this dev stack does not run (e.g. Apache/Nginx).
        // Linking dev as an extra OWNER does not change Project::isDemo() (name + demo-user
        // link are untouched), so demo side-effect suppression stays intact, and metrics
        // never mix (metric pushes are keyed per project API key).
        $demoProject = $this->projectRepository->findDemoProject(Project::DEMO_EMAIL, Project::DEMO_NAME);
        if ($demoProject !== null) {
            $demoLink = $this->findLink($user, $demoProject) ?? new ProjectUser();
            $demoLink->setUser($user);
            $demoLink->setProject($demoProject);
            $demoLink->setRole(ProjectRole::OWNER);
            $this->em->persist($demoLink);
            $io->writeln('Linked dev user to the demo project (OWNER).');
        } else {
            $io->note([
                'Demo project not set up yet — the dev↔demo link was skipped.',
                'Want the demo project too (synthetic data for every component)?',
                'Run `make demo` to create it, then `make provision` to link this dev user to it.',
            ]);
        }

        $this->em->flush();

        $io->success('Dev self-monitoring account and project provisioned.');
        $io->writeln(sprintf('Login: %s / %s', self::EMAIL, self::PASSWORD));

        return Command::SUCCESS;
    }

    private function findDevProject(User $user): ?Project
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
