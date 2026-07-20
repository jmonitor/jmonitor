<?php

declare(strict_types=1);

namespace App\Tests\Controller\Collector;

use App\Collector\CollectorRateLimiterProvider;
use App\Controller\Collector\CollectorController;
use App\Entity\Project;
use App\Message\MetricsReceivedMessage;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Repository\ProjectRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class CollectorControllerTest extends TestCase
{
    private const API_KEY = 'valid-key';
    private const PROJECT_ID = 42;

    public function testValidBatchIsAcceptedAndDispatched(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (MetricsReceivedMessage $message): Envelope {
                $this->assertSame(self::PROJECT_ID, $message->getProjectId());
                $this->assertSame('system', $message->getMetrics()[0]['name']);

                return new Envelope($message);
            });

        $response = $this->handle($this->request([['name' => 'system', 'version' => 1, 'metrics' => []]]), $bus);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function testMissingApiKeyIsUnauthorized(): void
    {
        $response = $this->handle($this->request([], apiKey: null), $this->neverDispatchingBus());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testMissingVersionHeaderIsBadRequest(): void
    {
        $response = $this->handle($this->request([], version: null), $this->neverDispatchingBus());

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUnknownApiKeyIsForbidden(): void
    {
        $response = $this->handle($this->request([], apiKey: 'unknown-key'), $this->neverDispatchingBus());

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testMalformedJsonIsBadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->handle($this->request(null, rawBody: '{invalid'), $this->neverDispatchingBus());
    }

    #[DataProvider('provideStructurallyInvalidPayloads')]
    public function testStructurallyInvalidPayloadIsBadRequest(mixed $payload): void
    {
        $response = $this->handle($this->request($payload), $this->neverDispatchingBus());

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public static function provideStructurallyInvalidPayloads(): iterable
    {
        yield 'empty list' => [[]];
        yield 'associative array instead of a list' => [['name' => 'system', 'version' => 1]];
        yield 'list of scalars' => [[1, 2, 3]];
        yield 'oversized batch' => [array_fill(0, 51, ['name' => 'system'])];
    }

    public function testRateLimitedRequestIsRejected(): void
    {
        $provider = $this->rateLimiterProvider(limit: 1);

        $first = $this->handle($this->request([['name' => 'system']]), rateLimiterProvider: $provider);
        $second = $this->handle($this->request([['name' => 'system']]), $this->neverDispatchingBus(), rateLimiterProvider: $provider);

        $this->assertSame(Response::HTTP_ACCEPTED, $first->getStatusCode());
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $second->getStatusCode());
        $this->assertTrue($second->headers->has('X-RateLimit-Retry-After'));
    }

    /**
     * The agent loop paces itself on this header. The self-hosted `no_limit`
     * policy yields Retry-After 0, which would make agents collect without
     * any pause: accepted pushes must advertise a pacing floor instead.
     */
    public function testAcceptedPushAdvertisesAPacingFloorEvenWithoutRateLimit(): void
    {
        $factory = new RateLimiterFactory([
            'id' => 'test',
            'policy' => 'no_limit',
        ], new InMemoryStorage());
        $provider = new CollectorRateLimiterProvider($factory, $factory, $factory, $factory, new PlanResolver(Edition::SELF_HOSTED));

        $response = $this->handle($this->request([['name' => 'system']]), rateLimiterProvider: $provider);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(10, (int) $response->headers->get('X-RateLimit-Retry-After'));
    }

    private function handle(Request $request, ?MessageBusInterface $bus = null, ?CollectorRateLimiterProvider $rateLimiterProvider = null): Response
    {
        if ($bus === null) {
            $bus = $this->createMock(MessageBusInterface::class);
            $bus->method('dispatch')->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));
        }

        $project = new Project();
        new \ReflectionProperty(Project::class, 'id')->setValue($project, self::PROJECT_ID);

        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static fn(array $criteria): ?Project => $criteria['apiKey'] === self::API_KEY ? $project : null,
        );

        return new CollectorController()->collectMetrics($request, $bus, $repository, $rateLimiterProvider ?? $this->rateLimiterProvider());
    }

    private function neverDispatchingBus(): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        return $bus;
    }

    private function rateLimiterProvider(int $limit = 10): CollectorRateLimiterProvider
    {
        $factory = new RateLimiterFactory([
            'id' => 'test',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 minute',
        ], new InMemoryStorage());

        return new CollectorRateLimiterProvider($factory, $factory, $factory, $factory, new PlanResolver(Edition::CLOUD));
    }

    private function request(mixed $payload, ?string $apiKey = self::API_KEY, ?string $version = '1.2.3', ?string $rawBody = null): Request
    {
        $server = [];

        if ($apiKey !== null) {
            $server['HTTP_X_JMONITOR_API_KEY'] = $apiKey;
        }

        if ($version !== null) {
            $server['HTTP_X_JMONITOR_VERSION'] = $version;
        }

        return Request::create('/metrics', 'POST', server: $server, content: $rawBody ?? json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
