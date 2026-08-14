<?php

declare(strict_types=1);

namespace App\Command\Demo;

use App\Demo\DemoAgentVersions;
use App\Demo\DemoBatchBuilder;
use App\Demo\State\DemoState;
use App\Entity\Project;
use App\Message\MetricsReceivedMessage;
use App\Repository\ProjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:demo:run', description: 'Generate and dispatch synthetic demo metrics in a loop')]
class DemoWorkerCommand extends Command implements SignalableCommandInterface
{
    private bool $stop = false;
    private ?Project $demoProject = null;

    public function __construct(
        private readonly DemoBatchBuilder $batchBuilder,
        private readonly DemoState $state,
        private readonly DemoAgentVersions $versions,
        private readonly MessageBusInterface $bus,
        private readonly ProjectRepository $projectRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Seconds between pushes', '15');
        $this->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Stop after N seconds');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->resolveDemoProject();

        if (!$project) {
            $output->writeln(sprintf('<error>Demo project not found. Run %s first.</error>', DemoProvisionCommand::COMMAND));

            return Command::FAILURE;
        }

        $interval = max(1, (int) $input->getOption('interval'));
        $timeLimit = $input->getOption('time-limit') !== null ? (int) $input->getOption('time-limit') : null;
        $startedAt = time();

        $output->writeln('Demo worker started');

        while (!$this->isStopping()) {
            try {
                $batch = $this->batchBuilder->build($project, $this->state);
                $this->bus->dispatch(new MetricsReceivedMessage((int) $project->getId(), $batch, $this->versions->collector(), $this->versions->bundle()));
                $this->state->persist();

                $output->writeln(sprintf('Pushed %d demo metric inputs', count($batch)), OutputInterface::VERBOSITY_VERBOSE);
            } catch (\Throwable $e) {
                $this->logger->error('Demo tick failed', ['exception' => $e]);
                $output->writeln(sprintf('<error>Demo tick failed: %s</error>', $e->getMessage()));
            }

            for ($i = 0; $i < $interval; $i++) {
                if ($this->isStopping()) {
                    break;
                }

                if ($timeLimit !== null && (time() - $startedAt) >= $timeLimit) {
                    $output->writeln('Time limit reached');

                    // Honest exit code: the run completed its time window without error.
                    // Recycling on time-limit is the orchestrator's job — CC_WORKER_RESTART
                    // (always), or worker-wrapper.sh which maps a natural stop to exit 1 for
                    // CC_WORKER_RESTART=on-failure.
                    return Command::SUCCESS;
                }

                sleep(1);
            }
        }

        $output->writeln('Demo worker stopped');

        return Command::SUCCESS;
    }

    private function resolveDemoProject(): ?Project
    {
        return $this->demoProject ??= $this->projectRepository->findDemoProject(
            DemoProvisionCommand::EMAIL,
            DemoProvisionCommand::PROJECT_NAME,
        );
    }

    public function getSubscribedSignals(): array
    {
        $signals = [];

        foreach (['SIGINT', 'SIGTERM', 'SIGQUIT'] as $signal) {
            if (defined($signal)) {
                $signals[] = constant($signal);
            }
        }

        return $signals;
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->stop = true;

        return false;
    }

    /** @phpstan-impure */
    private function isStopping(): bool
    {
        return $this->stop;
    }
}
