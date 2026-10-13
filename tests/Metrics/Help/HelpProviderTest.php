<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Help;

use App\Metrics\Help\HelpProvider;
use App\Metrics\Metric;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HelpProviderTest extends KernelTestCase
{
    private function provider(): HelpProvider
    {
        self::bootKernel();

        return self::getContainer()->get(HelpProvider::class);
    }

    public function testAMetricDocumentedInTheConfigHasHelp(): void
    {
        $this->assertTrue($this->provider()->hasHelp(Metric::SystemRamUsage));
    }

    public function testAMetricDocumentedByATemplateOnlyHasHelp(): void
    {
        $this->assertTrue($this->provider()->hasHelp(Metric::RedisInformations));
    }

    public function testAMetricWithoutAnyContentHasNoHelp(): void
    {
        $this->assertFalse($this->provider()->hasHelp(Metric::ApacheUptime));
    }

    /**
     * The dashboard shows these cards with a help icon; an icon opening an empty panel is a bug.
     */
    public function testTheRedisKeyspaceCardsAreDocumented(): void
    {
        $provider = $this->provider();

        $this->assertTrue($provider->hasHelp(Metric::RedisDbKeys));
        $this->assertTrue($provider->hasHelp(Metric::RedisDbExpiringKeys));
        $this->assertTrue($provider->hasHelp(Metric::RedisDbAvgTtl));
    }

    /**
     * In a PHP array literal the last duplicate key silently wins: the first text is dead.
     */
    public function testTheConfigDeclaresEachMetricOnce(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/config/metric_help.php');
        preg_match_all('/Metric::(\w+)->value => \[/', $source, $matches);

        $duplicates = array_filter(array_count_values($matches[1]), static fn(int $count): bool => $count > 1);

        $this->assertSame([], $duplicates);
    }
}
