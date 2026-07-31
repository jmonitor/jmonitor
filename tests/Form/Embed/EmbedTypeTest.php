<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Form\Embed\EmbedType;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Metric;
use App\Metrics\MetricLocator;
use App\Metrics\Renderer;
use App\Metrics\Renderer\ChartDefaultsResolver;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class EmbedTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    /** @return FormTypeInterface[] */
    protected function getTypes(): array
    {
        $container = new class implements ContainerInterface {
            public function get(string $id): never
            {
                throw new \LogicException('No metric service in this unit test.');
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        return [new EmbedType(new ChartDefaultsResolver(new MetricLocator($container)))];
    }

    public function testSingleTimeSeriesRendererMetricGetsTheChartOptions(): void
    {
        // MysqlQueriesPerSecond has a single Line renderer.
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::MysqlQueriesPerSecond]);

        $this->assertFalse($form->has('renderer'));
        $this->assertTrue($form->has('chart'));
        $this->assertTrue($form->get('chart')->has('range'));
        $this->assertTrue($form->get('chart')->has('aspectRatio'));
        $this->assertTrue($form->has('card'));
        $this->assertTrue($form->has('autoRefresh'));
    }

    /** Regression: the gauge used to get no options form at all. */
    public function testGaugeOnlyMetricGetsAnAspectRatio(): void
    {
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::PhpOpcacheHitRate]);

        $this->assertTrue($form->has('chart'));
        $this->assertTrue($form->get('chart')->has('aspectRatio'));
        $this->assertFalse($form->get('chart')->has('range'));
    }

    public function testRendererWithoutOptionsGetsNoChartSubform(): void
    {
        // ApacheLoadAverage's only renderer is Basic.
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::ApacheLoadAverage]);

        $this->assertFalse($form->has('chart'));
        $this->assertTrue($form->has('card'));
    }

    public function testMultiRendererMetricGetsTheStyleSelectAndTheGaugeOptions(): void
    {
        $form = $this->factory->create(EmbedType::class, [
            'renderer' => Renderer::Gauge,
            'chart' => new GaugeEmbedOptions(),
        ], ['metric' => Metric::SystemRamUsage]);

        $this->assertTrue($form->has('renderer'));
        $this->assertTrue($form->get('chart')->has('aspectRatio'));
        $this->assertFalse($form->get('chart')->has('range'));
    }

    public function testSwitchingToALineRendererBringsTheRangeBack(): void
    {
        $form = $this->factory->create(EmbedType::class, [
            'renderer' => Renderer::Gauge,
            'chart' => new GaugeEmbedOptions(),
        ], ['metric' => Metric::SystemRamUsage]);

        $form->submit(['renderer' => 'line', 'chart' => ['range' => 'last_1_hour', 'aspectRatio' => '4'], 'card' => [], 'autoRefresh' => '']);

        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, $form->get('chart')->getData());
    }
}
