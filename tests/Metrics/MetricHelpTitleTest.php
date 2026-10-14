<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Metrics\Metric;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MetricHelpTitleTest extends TestCase
{
    /**
     * @return iterable<string, array{Metric, string}>
     */
    public static function titles(): iterable
    {
        yield 'label alone is ambiguous' => [Metric::MysqlConfig, 'MySQL Config'];
        yield 'label already names the component' => [Metric::SystemInformations, 'System informations'];
        yield 'three PHP cards share the label Config' => [Metric::PhpOpcacheConfig, 'PHP OPcache config'];
        yield 'APCu' => [Metric::PhpApcuConfig, 'PHP APCu config'];
        yield 'base PHP config' => [Metric::PhpBaseConfig, 'PHP config'];
    }

    #[DataProvider('titles')]
    public function testTheHelpTitleIsUnambiguousOutOfTheDashboard(Metric $metric, string $expected): void
    {
        $this->assertSame($expected, $metric->helpTitle());
    }
}
