<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Bridge\InfluxDb\InfluxDb;
use App\Command\InstallCommand;
use App\Entity\Enums\Role;
use App\Entity\User;
use App\Install\AdminProvisioner;
use App\Install\EnvChecker;
use App\Install\SelfMonitoringProvisioner;
use App\Plan\Edition;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * The full install path (migrations, admin creation against real services) is
 * covered by the CI smoke test (.github/workflows/docker-publish.yml). Here we
 * pin two contracts: invalid config aborts BEFORE anything touches the
 * database or InfluxDB, and the self-monitoring project is provisioned only
 * on first boot (admin just created) of a self-hosted instance.
 */
final class InstallCommandTest extends TestCase
{
    public function testFailsFastOnPlaceholderConfigWithoutTouchingServices(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('executeQuery');

        $influxDb = $this->createMock(InfluxDb::class);
        $influxDb->expects($this->never())->method('getOrgId');

        // AdminProvisioner is readonly (PHPUnit cannot mock readonly classes):
        // use a real instance and assert through its mocked dependencies.
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $adminProvisioner = new AdminProvisioner($em, $userRepository, $this->createMock(UserPasswordHasherInterface::class));

        $command = new InstallCommand(
            new EnvChecker(appSecret: 'CHANGE_ME', mercureJwtSecret: 'short', adminEmail: '', adminPassword: ''),
            $adminProvisioner,
            $this->neverProvisioningSelfMonitoring(),
            Edition::SELF_HOSTED,
            $connection,
            $influxDb,
            adminEmail: '',
            adminPassword: '',
            selfMonitoringEnabled: true,
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('APP_SECRET', $tester->getDisplay());
        self::assertStringContainsString('MERCURE_JWT_SECRET', $tester->getDisplay());
    }

    public function testProvisionsTheSelfMonitoringProjectWhenTheAdminWasJustCreated(): void
    {
        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository->method('findOneBy')->willReturn(null);
        $projectEm = $this->createMock(EntityManagerInterface::class);
        $projectEm->expects($this->once())->method('flush');

        $tester = $this->runInstall(
            edition: Edition::SELF_HOSTED,
            selfMonitoringEnabled: true,
            adminAlreadyExists: false,
            selfMonitoringProvisioner: new SelfMonitoringProvisioner($projectEm, $projectRepository),
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Self-monitoring project ready: jmonitor', $tester->getDisplay());
    }

    public function testSkipsSelfMonitoringWhenAnAdminAlreadyExists(): void
    {
        $tester = $this->runInstall(
            edition: Edition::SELF_HOSTED,
            selfMonitoringEnabled: true,
            adminAlreadyExists: true,
            selfMonitoringProvisioner: $this->neverProvisioningSelfMonitoring(),
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringNotContainsString('Self-monitoring project', $tester->getDisplay());
    }

    public function testSkipsSelfMonitoringWhenDisabledByEnv(): void
    {
        $tester = $this->runInstall(
            edition: Edition::SELF_HOSTED,
            selfMonitoringEnabled: false,
            adminAlreadyExists: false,
            selfMonitoringProvisioner: $this->neverProvisioningSelfMonitoring(),
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringNotContainsString('Self-monitoring project', $tester->getDisplay());
    }

    public function testRefusesToRunOnTheCloudEdition(): void
    {
        $tester = $this->runInstall(
            edition: Edition::CLOUD,
            selfMonitoringEnabled: true,
            adminAlreadyExists: false,
            selfMonitoringProvisioner: $this->neverProvisioningSelfMonitoring(),
        );

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('self-hosted', $tester->getDisplay());
    }

    /**
     * Runs app:install with a valid config, a stubbed migrations sub-command
     * and reachable (mocked) MySQL/InfluxDB.
     */
    private function runInstall(Edition $edition, bool $selfMonitoringEnabled, bool $adminAlreadyExists, SelfMonitoringProvisioner $selfMonitoringProvisioner): CommandTester
    {
        $existingAdmin = new User();
        $existingAdmin->setRole(Role::ROLE_ADMIN);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturnCallback(
            static function (array $criteria) use ($adminAlreadyExists, $existingAdmin): ?User {
                if (($criteria['role'] ?? null) === Role::ROLE_ADMIN) {
                    return $adminAlreadyExists ? $existingAdmin : null;
                }

                return null; // no user with the admin email yet
            },
        );

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');
        $adminProvisioner = new AdminProvisioner($this->createMock(EntityManagerInterface::class), $userRepository, $hasher);

        $command = new InstallCommand(
            new EnvChecker(
                appSecret: 'a-real-secret',
                mercureJwtSecret: str_repeat('m', 32),
                adminEmail: 'admin@example.com',
                adminPassword: 'pw',
            ),
            $adminProvisioner,
            $selfMonitoringProvisioner,
            $edition,
            $this->createMock(Connection::class),
            $this->createMock(InfluxDb::class),
            adminEmail: 'admin@example.com',
            adminPassword: 'pw',
            selfMonitoringEnabled: $selfMonitoringEnabled,
        );

        $migrations = new Command('doctrine:migrations:migrate');
        $migrations->addOption('allow-no-migration', null, InputOption::VALUE_NONE);
        $migrations->setCode(static fn(InputInterface $input, OutputInterface $output): int => Command::SUCCESS);

        $application = new Application();
        $application->addCommand($migrations);
        $application->addCommand($command);

        $tester = new CommandTester($application->find(InstallCommand::COMMAND));
        $tester->execute([]);

        return $tester;
    }

    private function neverProvisioningSelfMonitoring(): SelfMonitoringProvisioner
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $repository = $this->createMock(ProjectRepository::class);
        $repository->expects($this->never())->method('findOneBy');

        return new SelfMonitoringProvisioner($em, $repository);
    }
}
