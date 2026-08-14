<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Bridge\InfluxDb\InfluxDb;
use App\Console\CommandLauncher;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Event\PostConsumeEvent;
use App\Metrics\Consumer;
use App\Metrics\Consumer\Consumer as ConsumerEnum;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\MetricsBagProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConsumerTest extends TestCase
{
    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('provideInvalidInputs')]
    public function testInvalidInputIsSkipped(array $input): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Invalid consumer input');

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($logger, $dispatchedEvent);

        $consumer->consume([$input], $this->createProject(), new \DateTimeImmutable(), '2.2.0');

        $this->assertSame([], $dispatchedEvent?->metricBags);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidInputs(): iterable
    {
        yield 'no name' => [['version' => 1, 'metrics' => [], 'duration' => 0.1]];
        yield 'no version' => [['name' => 'php', 'metrics' => [], 'duration' => 0.1]];
        yield 'unknown name' => [['name' => 'nope', 'version' => 1, 'metrics' => []]];
        yield 'unknown field' => [['name' => 'php', 'version' => 1, 'nope' => true]];
    }

    /**
     * Collectors only send 'threw' and 'skipped' when they are true.
     */
    public function testInputWithoutOptionalFieldsIsConsumed(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($logger, $dispatchedEvent);

        $consumer->consume(
            [['name' => 'php', 'version' => 1, 'metrics' => ['memory_usage' => 42], 'duration' => 0.1]],
            $this->createProject(),
            new \DateTimeImmutable(),
            '2.2.0',
        );

        $bag = $dispatchedEvent?->metricBags[ConsumerEnum::PHP->value] ?? null;

        $this->assertInstanceOf(MetricBagDto::class, $bag);
        $this->assertFalse($bag->hasThrew());
    }

    private function createProject(): Project
    {
        $project = new Project();
        $project->addComponent(Component::PHP);

        return $project;
    }

    private function createConsumer(LoggerInterface $logger, ?PostConsumeEvent &$dispatchedEvent): Consumer
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event) use (&$dispatchedEvent): object {
            $dispatchedEvent = $event;

            return $event;
        });

        return new Consumer(
            new ServiceLocator([ConsumerEnum::PHP->value => fn(): ConsumerInterface => $this->createPhpConsumer()]),
            $logger,
            $this->createMock(InfluxDb::class),
            $this->createMock(MetricsBagProvider::class),
            $eventDispatcher,
            Validation::createValidator(),
            $this->createMock(Security::class),
            $this->createMock(CommandLauncher::class),
            $this->createMock(EntityManagerInterface::class),
        );
    }

    private function createPhpConsumer(): ConsumerInterface
    {
        return new class implements ConsumerInterface {
            public function normalizeBag(MetricBagDto $bag): MetricBagDto
            {
                return $bag;
            }

            public function getMetricsToCache(MetricBagDto $bag): array
            {
                return [];
            }

            public function getInfluxPoints(MetricBagDto $bag): array
            {
                return [];
            }

            public function getConstraints(int $version): array
            {
                return [];
            }
        };
    }
}
