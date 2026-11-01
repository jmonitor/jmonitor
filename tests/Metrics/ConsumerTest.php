<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Bridge\InfluxDb\InfluxDb;
use App\Bridge\InfluxDb\TimestampedPoint;
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
use InfluxDB2\Point;
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
        yield 'version zero' => [['name' => 'php', 'version' => 0, 'metrics' => []]];
        yield 'version as string' => [['name' => 'php', 'version' => '1', 'metrics' => []]];
    }

    public function testVersionNewerThanTheConsumerIsSkipped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Unsupported consumer version');

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($logger, $dispatchedEvent);

        $consumer->consume([['name' => 'php', 'version' => 2, 'metrics' => []]], $this->createProject(), new \DateTimeImmutable(), '2.2.0');

        $this->assertSame([], $dispatchedEvent?->metricBags);
    }

    public function testLatestConsumerVersionIsConsumed(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($logger, $dispatchedEvent);

        $consumer->consume([['name' => 'apache', 'version' => 2, 'metrics' => []]], $this->createProject(), new \DateTimeImmutable(), '2.2.0');

        $this->assertSame(2, $dispatchedEvent?->metricBags[ConsumerEnum::APACHE->value]->getVersion());
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

    public function testASkippedEntryIsCachedAsSkipped(): void
    {
        $saved = null;
        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('saveMetricBag')->willReturnCallback(function (MetricBagDto $bag) use (&$saved): void {
            $saved = $bag;
        });

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($this->createMock(LoggerInterface::class), $dispatchedEvent, bagProvider: $bagProvider);

        $consumer->consume([['name' => 'php', 'version' => 1, 'skipped' => true, 'metrics' => []]], $this->createProject(), new \DateTimeImmutable(), '2.5.1');

        $this->assertTrue($saved?->hasSkipped());
        $this->assertTrue($dispatchedEvent?->metricBags['php']->hasSkipped());
    }

    public function testATimestampedPointKeepsItsTime(): void
    {
        $written = [];
        $influxDb = $this->createMock(InfluxDb::class);
        $influxDb->method('writePoints')->willReturnCallback(function (array $points) use (&$written): void {
            $written = $points;
        });
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $dispatchedEvent = null;
        $consumer = $this->createConsumer($this->createMock(LoggerInterface::class), $dispatchedEvent, influxDb: $influxDb, security: $security, points: [
            new TimestampedPoint('m', 1761917460, ['v' => 1]),
            new Point('m', fields: ['v' => 2]),
        ]);

        $project = $this->createProject();
        $project->setBucketId('bucket');
        $receivedAt = new \DateTimeImmutable('2026-10-31 15:00:00');

        $consumer->consume([['name' => 'php', 'version' => 1, 'metrics' => []]], $project, $receivedAt, '2.5.1');

        $this->assertCount(2, $written);
        $this->assertStringEndsWith(' 1761917460', $written[0]->toLineProtocol());
        $this->assertStringEndsWith(' ' . $receivedAt->getTimestamp(), $written[1]->toLineProtocol());
    }

    private function createProject(): Project
    {
        $project = new Project();
        $project->addComponent(Component::PHP);
        $project->addComponent(Component::Apache);

        return $project;
    }

    /**
     * @param Point[] $points
     */
    private function createConsumer(LoggerInterface $logger, ?PostConsumeEvent &$dispatchedEvent, ?MetricsBagProvider $bagProvider = null, ?InfluxDb $influxDb = null, ?Security $security = null, array $points = []): Consumer
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event) use (&$dispatchedEvent): object {
            $dispatchedEvent = $event;

            return $event;
        });

        return new Consumer(
            new ServiceLocator([
                ConsumerEnum::PHP->value => fn(): ConsumerInterface => $this->createNoopConsumer($points),
                ConsumerEnum::APACHE->value => fn(): ConsumerInterface => $this->createNoopConsumer(),
            ]),
            $logger,
            $influxDb ?? $this->createMock(InfluxDb::class),
            $bagProvider ?? $this->createMock(MetricsBagProvider::class),
            $eventDispatcher,
            Validation::createValidator(),
            $security ?? $this->createMock(Security::class),
            $this->createMock(CommandLauncher::class),
            $this->createMock(EntityManagerInterface::class),
        );
    }

    /**
     * @param Point[] $points
     */
    private function createNoopConsumer(array $points = []): ConsumerInterface
    {
        return new class ($points) implements ConsumerInterface {
            /**
             * @param Point[] $points
             */
            public function __construct(private readonly array $points) {}

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
                return $this->points;
            }

            public function getConstraints(int $version): array
            {
                return [];
            }
        };
    }
}
