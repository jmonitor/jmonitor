<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\Command\Subscription\PurgeExpiredSubscriptionsCommand;
use App\DependencyInjection\CloudOnlyScheduledTasksPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CloudOnlyScheduledTasksPassTest extends TestCase
{
    private ?string $originalEdition = null;

    protected function setUp(): void
    {
        $this->originalEdition = $_ENV['APP_EDITION'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEdition === null) {
            unset($_ENV['APP_EDITION'], $_SERVER['APP_EDITION']);

            return;
        }

        $_ENV['APP_EDITION'] = $_SERVER['APP_EDITION'] = $this->originalEdition;
    }

    public function testThePurgeTaskStaysScheduledOnCloud(): void
    {
        $container = $this->containerWithPurgeTask('cloud');

        (new CloudOnlyScheduledTasksPass())->process($container);

        self::assertTrue($container->getDefinition(PurgeExpiredSubscriptionsCommand::class)->hasTag('scheduler.task'));
    }

    public function testThePurgeTaskIsUnscheduledOnSelfHosted(): void
    {
        $container = $this->containerWithPurgeTask('selfhosted');

        (new CloudOnlyScheduledTasksPass())->process($container);

        $definition = $container->getDefinition(PurgeExpiredSubscriptionsCommand::class);

        self::assertFalse($definition->hasTag('scheduler.task'));
        self::assertTrue($definition->hasTag('console.command'), 'The command itself stays runnable.');
    }

    private function containerWithPurgeTask(string $edition): ContainerBuilder
    {
        $_ENV['APP_EDITION'] = $_SERVER['APP_EDITION'] = $edition;

        $container = new ContainerBuilder();

        $definition = (new Definition(PurgeExpiredSubscriptionsCommand::class))
            ->addTag('scheduler.task', ['trigger' => 'every', 'frequency' => '24 hours'])
            ->addTag('console.command');

        $container->setDefinition(PurgeExpiredSubscriptionsCommand::class, $definition);

        return $container;
    }
}
