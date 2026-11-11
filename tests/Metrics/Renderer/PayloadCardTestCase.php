<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Bridge\Eol\EndOfLife;
use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\MetricDtoProvider;
use App\Metrics\MetricRenderer;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer;
use App\Project\ProjectContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Renders a card from a pushed payload: the payload has to pass the consumer's constraints, and the
 * card reads the bag the consumer cached, not the raw payload.
 */
abstract class PayloadCardTestCase extends KernelTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->project = new Project();
        self::getContainer()->get(ProjectContext::class)->setCurrentProject($this->project);

        self::getContainer()->set(EndOfLife::class, $this->createStub(EndOfLife::class));

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get(RequestStack::class)->push($request);
    }

    /**
     * @param class-string<ConsumerInterface> $consumerClass
     * @param array<string, mixed>            $payload
     */
    protected function push(string $consumerClass, array $payload, string $receivedAt = 'now', bool $skipped = false): void
    {
        $consumer = Consumer::from(new \ReflectionClass($consumerClass)->getAttributes(AsTaggedItem::class)[0]->newInstance()->index);
        $service = self::getContainer()->get($consumerClass);

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($payload, $service->getConstraints($consumer->latestVersion()));
        $this->assertCount(0, $violations, 'The payload is rejected before it reaches a card: ' . $violations);

        $bag = $service->normalizeBag(MetricBagDto::create($this->project, $consumer, $consumer->latestVersion(), $payload, new \DateTimeImmutable($receivedAt), false, $skipped));
        $cached = array_filter($service->getMetricsToCache($bag), static fn(mixed $value): bool => $value !== null);

        self::getContainer()->get(MetricsBagProvider::class)->saveMetricBag($bag->withMetrics($cached));
        self::getContainer()->get(MetricsBagProvider::class)->reset();
        self::getContainer()->get(MetricDtoProvider::class)->reset();
        self::getContainer()->get(DeltaCalculator::class)->reset();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function renderCard(Metric $metric, ?Renderer $renderer = null, array $options = []): string
    {
        return self::getContainer()->get(MetricRenderer::class)->render($metric, $renderer, dtoOptions: $options);
    }
}
