<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Process\Messenger\RunProcessMessage;

/**
 * Launches a command through the message bus,
 * synchronously or asynchronously, or using the default from the messenger.yml configuration.
 */
readonly class CommandLauncher
{
    public function __construct(
        private MessageBusInterface $bus,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    /**
     * @param StampInterface[] $stamps
     */
    public function launchAsync(string|array $input, array $stamps = []): Envelope
    {
        return $this->launch($input, true, $stamps);
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function launchSync(string|array $input, array $stamps = []): Envelope
    {
        return $this->launch($input, false, $stamps);
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function launch(string|array $input, ?bool $async, array $stamps = []): Envelope
    {
        if (is_array($input)) {
            $input = implode(' ', $input);
        }

        if ($async !== null) {
            $stamps[] = new TransportNamesStamp($async ? 'async' : 'sync');
        }

        return $this->bus->dispatch(new RunCommandMessage($input), $stamps);
    }

    /**
     * Kept in case it is needed someday.
     * @param string[] $command
     */
    public function lauchProcess(array $command, ?bool $async): Envelope
    {
        $stamps = [];

        if ($async !== null) {
            $stamps[] = new TransportNamesStamp($async ? 'async' : 'sync');
        }

        return $this->bus->dispatch(new RunProcessMessage($command, $this->projectDir, timeout: 60 * 5), $stamps);
    }
}
