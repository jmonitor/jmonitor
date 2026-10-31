<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\MetricRenderer;
use App\Metrics\MetricsBagProvider;
use App\Project\ProjectContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CloudflareZoneCardsTest extends KernelTestCase
{
    public function testCertificateDaysLeftAndBadge(): void
    {
        $payload = self::fixture();
        $payload['zone']['certificate']['expires_at'] = time() + 47 * 86400 + 3600;
        $html = $this->render(Metric::CloudflareCertificate, $payload);
        $this->assertStringContainsString('>47<', preg_replace('/\s+/', '', $html) ?? '');
        $this->assertStringContainsString('badge-success', $html);

        $payload['zone']['certificate']['expires_at'] = time() + 10 * 86400 + 3600;
        $this->assertStringContainsString('badge-warning', $this->render(Metric::CloudflareCertificate, $payload));

        $payload['zone']['certificate']['status'] = 'pending';
        $this->assertStringContainsString('badge-danger', $this->render(Metric::CloudflareCertificate, $payload));
    }

    public function testAMissingCertificateKeyShowsTheForbiddenCopy(): void
    {
        $payload = self::fixture();
        unset($payload['zone']['certificate']);

        $html = $this->render(Metric::CloudflareCertificate, $payload);

        $this->assertStringContainsString('lacks the permission', $html);
        $this->assertStringContainsString('https://github.com/jmonitor/collector?tab=readme-ov-file#cloudflare', $html);
    }

    public function testAMissingSettingsKeyShowsTheForbiddenCopy(): void
    {
        $payload = self::fixture();
        unset($payload['zone']['settings']);

        $this->assertStringContainsString('lacks the permission', $this->render(Metric::CloudflareSettings, $payload));
        $this->assertStringContainsString('lacks the permission', $this->render(Metric::CloudflareCacheSettings, $payload));
    }

    public function testTheZoneCardFlagsDevelopmentMode(): void
    {
        $payload = self::fixture();
        $this->assertStringContainsString('jmonitor.io', $this->render(Metric::CloudflareZone, $payload));
        $this->assertStringNotContainsString('badge-danger', $this->render(Metric::CloudflareZone, $payload));

        $payload['zone']['development_mode'] = 3600;
        $this->assertStringContainsString('badge-danger', $this->render(Metric::CloudflareZone, $payload));
    }

    public function testTheWholePageIsDownWhenSkipped(): void
    {
        $html = $this->render(Metric::CloudflareZone, [], skipped: true);

        $this->assertStringContainsString('could not start the Cloudflare collector', $html);
    }

    public function testAnUnavailableZoneShowsItsReason(): void
    {
        $html = $this->render(Metric::CloudflareZone, ['unavailable' => ['zone' => 'rate_limited']]);

        $this->assertStringContainsString('rate limit reached', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../Consumer/Cloudflare/fixtures/payload_v1.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function render(Metric $metric, array $payload, bool $skipped = false): string
    {
        self::bootKernel();

        $project = new Project();
        self::getContainer()->get(ProjectContext::class)->setCurrentProject($project);

        $bag = MetricBagDto::create($project, Consumer::CLOUDFLARE, 1, $payload, new \DateTimeImmutable(), false, $skipped);

        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getLastBag')->willReturn($bag);
        $bagProvider->method('getComponentBags')->willReturn(['cloudflare' => $bag]);
        self::getContainer()->set(MetricsBagProvider::class, $bagProvider);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get(RequestStack::class)->push($request);

        return self::getContainer()->get(MetricRenderer::class)->render($metric);
    }
}
