<?php

declare(strict_types=1);

namespace App\Command\Messenger;

use App\Console\CommandLauncher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand('app:messenger-monitor:purge', 'Purge the messenger monitor logs')]
#[AsPeriodicTask('24 hours', from: '04:00', jitter: 5)]
class MessengerMonitorPurger extends Command
{
    private CommandLauncher $commandLauncher;

    public function __construct(CommandLauncher $commandLauncher)
    {
        parent::__construct();

        $this->commandLauncher = $commandLauncher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandLauncher->launchSync('messenger:monitor:purge --older-than 1-week');

        return Command::SUCCESS;
    }
}
