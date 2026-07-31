<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Chart\Dto\GaugeChartConfiguration;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Metric;
use App\Metrics\Renderer\ChartDefaultsResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A slider cannot represent a default that sits outside its bounds — the situation the
 * previous Range(0.1, 5) constraint was in, with a metric defaulting to 6.
 */
class AspectRatioBoundsTest extends KernelTestCase
{
    public function testEveryResolvedDefaultFitsInsideTheSliderBounds(): void
    {
        self::bootKernel();
        $resolver = self::getContainer()->get(ChartDefaultsResolver::class);
        $this->assertInstanceOf(ChartDefaultsResolver::class, $resolver);

        foreach (Metric::cases() as $metric) {
            // The sidebar form is built from findRenderer() (defaultRenderer() when none is
            // chosen), which is not always inside availableRenderers() (e.g. MysqlSlowQueriesCount
            // defaults to Line but only offers Bar/Basic) — check the actual pair the form uses too.
            $renderers = [...$metric->availableRenderers(), $metric->defaultRenderer()];

            foreach (array_unique($renderers, SORT_REGULAR) as $renderer) {
                $config = $resolver->resolve($metric, $renderer);

                if ($config instanceof TimeSeriesChartConfiguration) {
                    // The slider never sees a null aspectRatio directly: EmbedType/TimeSeriesEmbedOptionsType
                    // falls back to the canonical default, so that's the value the bounds must hold for.
                    $aspectRatio = $config->aspectRatio ?? TimeSeriesChartConfiguration::DEFAULT_ASPECT_RATIO;
                    $this->assertGreaterThanOrEqual(TimeSeriesEmbedOptions::ASPECT_RATIO_MIN, $aspectRatio, sprintf('%s / %s', $metric->value, $renderer->value));
                    $this->assertLessThanOrEqual(TimeSeriesEmbedOptions::ASPECT_RATIO_MAX, $aspectRatio, sprintf('%s / %s', $metric->value, $renderer->value));
                }

                if ($config instanceof GaugeChartConfiguration) {
                    $this->assertGreaterThanOrEqual(GaugeEmbedOptions::ASPECT_RATIO_MIN, $config->aspectRatio, sprintf('%s / %s', $metric->value, $renderer->value));
                    $this->assertLessThanOrEqual(GaugeEmbedOptions::ASPECT_RATIO_MAX, $config->aspectRatio, sprintf('%s / %s', $metric->value, $renderer->value));
                }
            }
        }
    }
}
