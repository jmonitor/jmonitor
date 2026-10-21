<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Apache;

use App\Entity\Project;
use App\Metrics\Consumer\Apache\ApacheConsumer;
use App\Metrics\Consumer\Consumer;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

class ApacheConsumerTest extends TestCase
{
    public function testV1PayloadIsValid(): void
    {
        $this->assertCount(0, $violations = $this->validate($this->v1Payload(), 1), (string) $violations);
    }

    public function testV1RejectsFloatAverages(): void
    {
        $violations = $this->validate(['requests_per_second' => 0.25, 'duration_per_request' => 1.5] + $this->v1Payload(), 1);

        $this->assertSame(['[requests_per_second]', '[duration_per_request]'], $this->paths($violations));
    }

    public function testV1RejectsTotalDuration(): void
    {
        $violations = $this->validate(['total_duration' => 1200] + $this->v1Payload(), 1);

        $this->assertSame(['[total_duration]'], $this->paths($violations));
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('provideValidV2Payloads')]
    public function testV2PayloadIsValid(array $override): void
    {
        $this->assertCount(0, $violations = $this->validate($override + $this->v2Payload(), 2), (string) $violations);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideValidV2Payloads(): iterable
    {
        yield 'float averages' => [[]];
        yield 'int averages' => [['requests_per_second' => 3, 'duration_per_request' => 12]];
        yield 'total duration null' => [['total_duration' => null]];
    }

    public function testV2TotalDurationIsOptional(): void
    {
        $payload = $this->v2Payload();
        unset($payload['total_duration']);

        $this->assertCount(0, $violations = $this->validate($payload, 2), (string) $violations);
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('provideInvalidV2Payloads')]
    public function testV2RejectsInvalidValues(array $override, string $path): void
    {
        $this->assertSame([$path], $this->paths($this->validate($override + $this->v2Payload(), 2)));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidV2Payloads(): iterable
    {
        yield 'negative total duration' => [['total_duration' => -1], '[total_duration]'];
        yield 'float total duration' => [['total_duration' => 1.5], '[total_duration]'];
        yield 'negative duration per request' => [['duration_per_request' => -0.5], '[duration_per_request]'];
        yield 'string requests per second' => [['requests_per_second' => '0.25'], '[requests_per_second]'];
    }

    public function testBagReadsV1Averages(): void
    {
        $bag = $this->bag($this->v1Payload(), 1);

        $this->assertSame(1.0, $bag->requestsPerSecond);
        $this->assertSame(3.0, $bag->durationPerRequest);
        $this->assertNull($bag->totalDuration);
    }

    public function testBagReadsV2Averages(): void
    {
        $bag = $this->bag($this->v2Payload(), 2);

        $this->assertSame(0.00347222, $bag->requestsPerSecond);
        $this->assertSame(3.4, $bag->durationPerRequest);
        $this->assertSame(1020, $bag->totalDuration);
    }

    public function testNormalizeBagComputesIntervalAverages(): void
    {
        $consumer = new ApacheConsumer($deltaCalculator = new DeltaCalculator(new ArrayAdapter()));
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        $start = new \DateTimeImmutable('2026-10-21 10:00:00');

        $consumer->normalizeBag($this->bag($this->v2Payload(), 2, $project, $start));
        $deltaCalculator->reset();

        $bag = $consumer->normalizeBag($this->bag(
            ['total_accesses' => 330, 'total_bytes' => 1048576 + 30720, 'total_duration' => 1020 + 1500] + $this->v2Payload(),
            2,
            $project,
            $start->modify('+15 seconds'),
        ));

        $this->assertInstanceOf(ApacheBag::class, $bag);
        $this->assertSame(2.0, $bag->realRequestsPerSecond);
        $this->assertSame(2048.0, $bag->realBytesPerSecond);
        $this->assertSame(50.0, $bag->realDurationPerRequest);
        $this->assertStringContainsString('real_duration_per_request=50', (string) $consumer->getInfluxPoints($bag)[0]->toLineProtocol());
    }

    public function testNoDurationAverageWithoutTotalDuration(): void
    {
        $consumer = new ApacheConsumer($deltaCalculator = new DeltaCalculator(new ArrayAdapter()));
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        $start = new \DateTimeImmutable('2026-10-21 10:00:00');

        $consumer->normalizeBag($this->bag($this->v1Payload(), 1, $project, $start));
        $deltaCalculator->reset();

        $bag = $consumer->normalizeBag($this->bag(['total_accesses' => 330] + $this->v1Payload(), 1, $project, $start->modify('+15 seconds')));

        $this->assertInstanceOf(ApacheBag::class, $bag);
        $this->assertSame(2.0, $bag->realRequestsPerSecond);
        $this->assertNull($bag->realDurationPerRequest);
    }

    /**
     * @param array<string, mixed> $metrics
     */
    private function validate(array $metrics, int $version): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate(
            $metrics,
            new ApacheConsumer($this->createMock(DeltaCalculator::class))->getConstraints($version),
        );
    }

    /**
     * @return string[]
     */
    private function paths(ConstraintViolationListInterface $violations): array
    {
        return array_map(fn(ConstraintViolationInterface $violation): string => $violation->getPropertyPath(), iterator_to_array($violations));
    }

    /**
     * @param array<string, mixed> $metrics
     */
    private function bag(array $metrics, int $version, ?Project $project = null, ?\DateTimeImmutable $receivedAt = null): ApacheBag
    {
        $bag = MetricBagDto::create($project ?? $this->createMock(Project::class), Consumer::APACHE, $version, $metrics, $receivedAt ?? new \DateTimeImmutable(), false);
        $this->assertInstanceOf(ApacheBag::class, $bag);

        return $bag;
    }

    /**
     * @return array<string, mixed>
     */
    private function v1Payload(): array
    {
        return [
            'server_version' => 'Apache/2.4.66 (Unix)',
            'server_mpm' => 'event',
            'uptime' => 86400,
            'load1' => 0.42,
            'load5' => 0.38,
            'load15' => 0.31,
            'total_accesses' => 300,
            'total_bytes' => 1048576,
            'requests_per_second' => 1,
            'bytes_per_second' => 12,
            'bytes_per_request' => 3495,
            'duration_per_request' => 3,
            'workers' => ['busy' => 1, 'idle' => 74],
            'scoreboard' => ['_' => 74, 'W' => 1, '.' => 325],
            'modules' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function v2Payload(): array
    {
        return [
            'requests_per_second' => 0.00347222,
            'duration_per_request' => 3.4,
            'total_duration' => 1020,
        ] + $this->v1Payload();
    }
}
