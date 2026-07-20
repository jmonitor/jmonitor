<?php

declare(strict_types=1);

namespace App\Tests\Chart\Configurator;

use App\Chart\Configurator\LineDefaultOptionsConfigurator;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Range\RangeContext;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Chartjs\Model\Chart;

class LineDefaultOptionsConfiguratorTest extends TestCase
{
    /**
     * The x-axis bounds must carry an explicit UTC offset so that Chart.js / the
     * moment adapter anchors them to an absolute instant. A timezone-naive string
     * ("Y-m-d H:i:s") is parsed by moment in the *browser* timezone, while the
     * InfluxDB data points are UTC — any non-UTC viewer then sees the series
     * shifted out of the visible window (empty charts). See the demo dashboard
     * regression on the Docker dev env (PHP tz UTC vs browser CEST).
     */
    public function test_x_axis_bounds_carry_an_explicit_timezone_offset(): void
    {
        $config = (new TimeSeriesChartConfiguration())->setRange(TimeRange::LAST_1_HOUR);

        $configurator = new LineDefaultOptionsConfigurator($this->createMock(RangeContext::class));

        $chart = new Chart(Chart::TYPE_LINE);
        $chart->setData(['datasets' => []]);

        $configurator->configure($chart, $config);

        $xAxis = $chart->getOptions()['scales']['x'];

        // e.g. "2026-07-10T16:24:31+00:00" — must end with a +HH:MM / -HH:MM offset.
        self::assertMatchesRegularExpression('/[+-]\d{2}:\d{2}$/', (string) $xAxis['min']);
        self::assertMatchesRegularExpression('/[+-]\d{2}:\d{2}$/', (string) $xAxis['max']);
    }
}
