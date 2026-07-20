<?php

declare(strict_types=1);

namespace App\Tests\Command\Subscription;

use App\Bridge\InfluxDb\InfluxDb;
use App\Command\Subscription\PurgeExpiredSubscriptionsCommand;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class PurgeExpiredSubscriptionsCommandTest extends TestCase
{
    public function testSelfHostedEditionSkipsPurgeEntirely(): void
    {
        $repository = $this->createMock(SubscriptionRepository::class);
        $repository->expects($this->never())->method('findExpiredBefore');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $influxDb = new InfluxDb(
            'http://localhost:8086',
            'test-token',
            'test-org-id',
            'test-org',
            new NullLogger(),
            $this->createMock(ClientInterface::class),
            new PlanResolver(Edition::SELF_HOSTED),
        );

        $command = new PurgeExpiredSubscriptionsCommand(
            $influxDb,
            $repository,
            $em,
            new NullLogger(),
            new PlanResolver(Edition::SELF_HOSTED),
            Edition::SELF_HOSTED,
        );

        $io = new SymfonyStyle(new ArrayInput([]), new BufferedOutput());

        $this->assertSame(Command::SUCCESS, $command->__invoke($io));
    }
}
