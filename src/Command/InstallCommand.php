<?php

declare(strict_types=1);

namespace App\Command;

use App\Bridge\InfluxDb\InfluxDb;
use App\Install\AdminProvisioner;
use App\Install\EnvChecker;
use App\Install\SelfMonitoringProvisioner;
use App\Plan\Edition;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Self-hosted first-start installer, run by the container entrypoint at every
 * startup (docker/selfhosted/entrypoint.sh). Idempotent: migrations always
 * run, the admin is only created when none exists, the self-monitoring
 * project only on first boot (opt-out: SELF_MONITORING=0). Refuses the cloud
 * edition and is hidden from `bin/console list`. Extends Command because it
 * needs getApplication() to run the migrations sub-command.
 */
#[AsCommand(name: self::COMMAND, description: 'Validate config, wait for services, run migrations and create the initial admin', hidden: true)]
class InstallCommand extends Command
{
    public const string COMMAND = 'app:install';

    private const int DB_WAIT_TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly EnvChecker $envChecker,
        private readonly AdminProvisioner $adminProvisioner,
        private readonly SelfMonitoringProvisioner $selfMonitoringProvisioner,
        private readonly Edition $edition,
        private readonly Connection $connection,
        private readonly InfluxDb $influxDb,
        #[Autowire(env: 'ADMIN_EMAIL')]
        private readonly string $adminEmail,
        #[Autowire(env: 'ADMIN_PASSWORD')]
        private readonly string $adminPassword,
        #[Autowire(env: 'bool:SELF_MONITORING')]
        private readonly bool $selfMonitoringEnabled,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->edition->isSelfHosted()) {
            $io->error('app:install is the self-hosted first-start installer and must not run on the cloud edition.');

            return Command::FAILURE;
        }

        $errors = $this->envChecker->check();
        if ($errors !== []) {
            $io->error(array_merge(['Invalid configuration:'], $errors));

            return Command::FAILURE;
        }

        if (!$this->waitForDatabase($io)) {
            return Command::FAILURE;
        }

        try {
            $orgId = $this->influxDb->getOrgId();
            $io->writeln(sprintf('InfluxDB reachable (org id: %s).', $orgId));
        } catch (\Throwable $e) {
            $io->error(sprintf('InfluxDB is unreachable or misconfigured: %s — check INFLUXDB_URL / INFLUXDB_TOKEN / INFLUXDB_ORG_NAME.', $e->getMessage()));

            return Command::FAILURE;
        }

        $exitCode = $this->runMigrations($output);
        if ($exitCode !== Command::SUCCESS) {
            $io->error('Doctrine migrations failed.');

            return Command::FAILURE;
        }

        try {
            $admin = $this->adminProvisioner->provision($this->adminEmail, $this->adminPassword);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->writeln($admin !== null
            ? sprintf('Admin account created: %s', $admin->getEmail())
            : 'An admin account already exists, skipping creation.');

        // First boot only ($admin freshly created): a self-monitoring project
        // deleted later by the admin must never come back on restart.
        if ($admin !== null && $this->selfMonitoringEnabled) {
            $project = $this->selfMonitoringProvisioner->provision($admin);
            $io->writeln(sprintf('Self-monitoring project ready: %s', $project->getName()));
        }

        $io->success('Install complete.');

        return Command::SUCCESS;
    }

    private function waitForDatabase(SymfonyStyle $io): bool
    {
        $deadline = time() + self::DB_WAIT_TIMEOUT_SECONDS;

        while (true) {
            try {
                $this->connection->executeQuery('SELECT 1');

                return true;
            } catch (\Throwable $e) {
                if (time() >= $deadline) {
                    $io->error(sprintf('Database unreachable after %ds: %s — check DATABASE_URL.', self::DB_WAIT_TIMEOUT_SECONDS, $e->getMessage()));

                    return false;
                }

                $io->writeln('Waiting for the database...');
                sleep(2);
            }
        }
    }

    private function runMigrations(OutputInterface $output): int
    {
        $application = $this->getApplication() ?? throw new \LogicException('Console application not set.');

        $input = new ArrayInput(['--allow-no-migration' => true]);
        $input->setInteractive(false);

        return $application->find('doctrine:migrations:migrate')->run($input, $output);
    }
}
