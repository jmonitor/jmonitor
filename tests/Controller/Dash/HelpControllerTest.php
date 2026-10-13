<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash;

use App\Metrics\Help\HelpProvider;
use App\Metrics\Metric;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Twig\Environment;

class HelpControllerTest extends WebTestCase
{
    private function client(): KernelBrowser
    {
        return static::createClient([], ['HTTP_HOST' => 'dash.jmonitor.io']);
    }

    public function testAMetricDocumentedInTheConfigIsPublic(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'RAM usage');
        $this->assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));
    }

    public function testAMetricDocumentedByATemplateOnlyIsPublic(): void
    {
        $client = $this->client();
        $client->request('GET', '/help/metric/redis.informations');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Templates using badges and typical ranges must render under the standalone shell too.
     */
    public function testTemplatesWithBadgesRenderStandalone(): void
    {
        $client = $this->client();

        $client->request('GET', '/help/metric/redis.stats');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.badge');

        $client->request('GET', '/help/metric/mysql.slow_queries_top');
        $this->assertResponseIsSuccessful();
    }

    public function testAnUnknownKeyIs404(): void
    {
        $client = $this->client();
        $client->request('GET', '/help/metric/does.not_exist');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAKnownMetricWithoutAnyContentIs404(): void
    {
        // apache.uptime is a Metric case with neither a metric_help.php entry nor a template.
        $client = $this->client();
        $client->request('GET', '/help/metric/apache.uptime');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testThePageLoadsNoJavaScript(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage?theme=dark');

        $this->assertCount(0, $crawler->filter('script'));
        $this->assertCount(0, $crawler->filter('turbo-frame'));
        $this->assertCount(2, $crawler->filter('link[rel="stylesheet"]'));
    }

    public function testTheDashboardOffcanvasShellIsStillTheDefault(): void
    {
        self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $html = $twig->render('dash/help/metric/_default.html.twig', ['metric' => Metric::SystemRamUsage]);

        $this->assertStringContainsString('<turbo-frame id="tf-off-canvas-end"', $html);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $html);
    }
}
