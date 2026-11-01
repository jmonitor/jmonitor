<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Entity\Project;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\MetricDtoProvider;
use App\Metrics\MetricRenderer;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Project\ProjectContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Renders one Cloudflare card against a payload, through the real metric pipeline: the bag the card
 * reads is the one the consumer would have cached, not the raw payload.
 */
abstract class CloudflareCardTestCase extends KernelTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../Consumer/Cloudflare/fixtures/payload_v1.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function render(Metric $metric, array $payload, bool $skipped = false, ?Renderer $renderer = null): string
    {
        $this->cache($payload, $skipped);

        return self::getContainer()->get(MetricRenderer::class)->render($metric, $renderer);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function dto(Metric $metric, Renderer $renderer, array $payload): ?AbstractDto
    {
        $this->cache($payload);

        return self::getContainer()->get(MetricDtoProvider::class)->getDto($metric, $renderer);
    }

    /**
     * @return array<string, string> label => value of every InfoLine in the html
     */
    protected static function lines(string $html): array
    {
        $lines = [];
        new Crawler($html)->filter('.dldt')->each(function (Crawler $line) use (&$lines): void {
            $lines[trim($line->filter('.dl')->text())] = trim(preg_replace('/\s+/', ' ', $line->filter('.dt')->text()) ?? '');
        });

        return $lines;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function cache(array $payload, bool $skipped = false): void
    {
        self::bootKernel();

        $project = new Project();
        self::getContainer()->get(ProjectContext::class)->setCurrentProject($project);

        $received = MetricBagDto::create($project, Consumer::CLOUDFLARE, 1, $payload, new \DateTimeImmutable(), false, $skipped);
        $cached = array_filter(new CloudflareConsumer()->getMetricsToCache($received), fn($value): bool => $value !== null);
        $bag = $received->withMetrics($cached);

        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getLastBag')->willReturn($bag);
        $bagProvider->method('getComponentBags')->willReturn(['cloudflare' => $bag]);
        self::getContainer()->set(MetricsBagProvider::class, $bagProvider);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get(RequestStack::class)->push($request);
    }
}
