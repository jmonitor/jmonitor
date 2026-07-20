<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\SelfMonitoringEnvVarProcessor;
use App\Entity\Project;
use App\Plan\Edition;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SelfMonitoringEnvVarProcessorTest extends TestCase
{
    public function testANonEmptyEnvVarAlwaysWins(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchOne');

        $processor = new SelfMonitoringEnvVarProcessor($connection, Edition::SELF_HOSTED, selfMonitoringEnabled: true);
        $value = $processor->getEnv('selfmonitoring', 'JMONITOR_API_KEY', static fn(string $name): string => 'explicit-key');

        self::assertSame('explicit-key', $value);
    }

    public function testFallsBackToTheSelfMonitoringProjectKeyOnSelfHosted(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')
            ->with(
                'SELECT api_key FROM project WHERE uuid = ?',
                [Uuid::fromString(Project::SELF_MONITORING_UUID)->toBinary()],
            )
            ->willReturn('db-key');

        $processor = new SelfMonitoringEnvVarProcessor($connection, Edition::SELF_HOSTED, selfMonitoringEnabled: true);
        $value = $processor->getEnv('selfmonitoring', 'JMONITOR_API_KEY', static fn(string $name): string => '');

        self::assertSame('db-key', $value);
    }

    public function testResolvesToEmptyStringWhenNoProjectExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);

        $processor = new SelfMonitoringEnvVarProcessor($connection, Edition::SELF_HOSTED, selfMonitoringEnabled: true);

        self::assertSame('', $processor->getEnv('selfmonitoring', 'JMONITOR_API_KEY', static fn(string $name): string => ''));
    }

    public function testDoesNotTouchTheDatabaseOnCloud(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchOne');

        $processor = new SelfMonitoringEnvVarProcessor($connection, Edition::CLOUD, selfMonitoringEnabled: true);

        self::assertSame('', $processor->getEnv('selfmonitoring', 'JMONITOR_API_KEY', static fn(string $name): string => ''));
    }

    public function testDoesNotTouchTheDatabaseWhenSelfMonitoringIsDisabled(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchOne');

        $processor = new SelfMonitoringEnvVarProcessor($connection, Edition::SELF_HOSTED, selfMonitoringEnabled: false);

        self::assertSame('', $processor->getEnv('selfmonitoring', 'JMONITOR_API_KEY', static fn(string $name): string => ''));
    }
}
