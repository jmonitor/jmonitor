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
        $client = static::createClient([], ['HTTP_HOST' => 'dash.jmonitor.io']);
        $client->disableReboot();

        return $client;
    }

    public function testAMetricDocumentedInTheConfigIsPublic(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'RAM usage');
        $this->assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));
    }

    /**
     * Out of the dashboard, four bundle keys are labelled "Config": the component tells them apart.
     */
    public function testTheTitleNamesTheComponentWhenTheLabelDoesNot(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/mysql.config');

        $this->assertSame('MySQL Config | Jmonitor help', $crawler->filter('title')->text());
        $this->assertSelectorTextContains('h1', 'MySQL Config');
    }

    public function testTheTitleDoesNotRepeatTheComponent(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.informations');

        $this->assertSame('System informations | Jmonitor help', $crawler->filter('title')->text());
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

    public function testThemeLightIsApplied(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage?theme=light');

        $this->assertSame('light', $crawler->filter('html')->attr('data-bs-theme'));
        $this->assertCount(0, $crawler->filter('script'));
    }

    public function testThemeDarkIsApplied(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage?theme=dark');

        $this->assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));
        $this->assertCount(0, $crawler->filter('script'));
    }

    public function testWithoutThemeThePageFollowsTheSystemPreference(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage');

        $this->assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));
        $this->assertCount(1, $crawler->filter('script'));
        $this->assertStringContainsString('prefers-color-scheme: light', $crawler->filter('script')->text());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidThemes(): iterable
    {
        yield 'unknown value' => ['?theme=sepia'];
        yield 'empty value' => ['?theme='];
        yield 'array value' => ['?theme[]=light'];
    }

    #[DataProvider('invalidThemes')]
    public function testAnInvalidThemeIsIgnored(string $query): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/system.ram_usage' . $query);

        $this->assertResponseIsSuccessful();
        $this->assertSame('dark', $crawler->filter('html')->attr('data-bs-theme'));
        $this->assertCount(1, $crawler->filter('script'));
    }

    public function testThePageIsPubliclyCacheableAndNotIndexed(): void
    {
        $client = $this->client();
        $client->request('GET', '/help/metric/system.ram_usage?theme=dark');

        $cacheControl = $client->getResponse()->headers->get('Cache-Control');
        $this->assertStringContainsString('public', (string) $cacheControl);
        $this->assertStringContainsString('max-age=3600', (string) $cacheControl);
        $this->assertResponseHeaderSame('X-Robots-Tag', 'noindex');
        $this->assertResponseHeaderSame('Content-Security-Policy', 'frame-ancestors *');
        $this->assertFalse($client->getResponse()->headers->has('Set-Cookie'));
        $this->assertSelectorExists('meta[name="robots"][content="noindex"]');
    }

    /**
     * Framed in a back-office, an in-place navigation would replace the help with the target site.
     */
    public function testEveryLinkOpensInANewTab(): void
    {
        $client = $this->client();
        $container = self::getContainer();
        $helpProvider = $container->get(HelpProvider::class);
        $checked = 0;

        foreach (Metric::cases() as $metric) {
            if (!$helpProvider->hasHelp($metric)) {
                continue;
            }

            $crawler = $client->request('GET', '/help/metric/' . $metric->value . '?theme=dark');
            $this->assertResponseIsSuccessful($metric->value);

            foreach ($crawler->filter('a[href]') as $link) {
                $this->assertInstanceOf(\DOMElement::class, $link);
                $this->assertSame('_blank', $link->getAttribute('target'), $metric->value . ': ' . $link->getAttribute('href'));
                $this->assertStringContainsString('noopener', $link->getAttribute('rel'), $metric->value . ': ' . $link->getAttribute('href'));
                ++$checked;
            }
        }

        $this->assertGreaterThan(10, $checked, 'the help content is expected to contain links');
    }

    /**
     * The keys jmonitor/easyadmin-bundle links to. Removing one here breaks every installed bundle.
     *
     * @return iterable<string, array{string}>
     */
    public static function bundleKeys(): iterable
    {
        $keys = [
            'system.ram_usage', 'system.cpu_usage', 'system.disk_usage', 'system.informations',
            'php.version', 'php.base_config', 'php.opcache_memory', 'php.opcache_hit_rate', 'php.opcache_config',
            'php.apcu_memory_usage', 'php.apcu_hit_rate', 'php.apcu_config', 'php.fpm_active_processes',
            'php.fpm_memory_peak_percent', 'php.fpm_config',
            'mysql.informations', 'mysql.queries_per_sec', 'mysql.connections_usage', 'mysql.innodb_buffer_pool_usage',
            'mysql.innodb_buffer_pool_hit_rate', 'mysql.tmp_tables', 'mysql.thread_cache_miss',
            'mysql.tables_lock_waits_percent', 'mysql.slow_queries_top', 'mysql.config',
            'redis.informations', 'redis.memory_usage', 'redis.hit_rate', 'redis.stats', 'redis.persistence', 'redis.db_keys',
            'symfony.version', 'symfony.messenger', 'symfony.scheduler_tasks', 'symfony.flex',
        ];

        foreach ($keys as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('bundleKeys')]
    public function testEveryKeyUsedByTheEasyAdminBundleAnswers(string $key): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/help/metric/' . $key . '?theme=dark');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('main h5')->count(), $key . ' renders no help section');
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
