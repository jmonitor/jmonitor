<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Chart\TimeRange;
use App\Form\Embed\EmbedType;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class EmbedTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testNoMetricFieldAndNoStyleSelectForSingleRendererMetric(): void
    {
        // MysqlQueriesPerSecond has a single renderer (Line), which supports a range.
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::MysqlQueriesPerSecond]);

        $this->assertFalse($form->has('metric'));
        $this->assertFalse($form->has('renderer'));
        $this->assertTrue($form->has('range'));
        $this->assertTrue($form->has('chartConfig'));
        $this->assertTrue($form->has('autoRefresh'));
    }

    public function testGaugeOnlyMetricGetsOnlyTheHiddenAutoRefreshField(): void
    {
        // PhpOpcacheHitRate has a single Gauge renderer: no range, no chart config.
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::PhpOpcacheHitRate]);

        $this->assertFalse($form->has('renderer'));
        $this->assertFalse($form->has('range'));
        $this->assertFalse($form->has('chartConfig'));
        $this->assertTrue($form->has('autoRefresh'));
    }

    public function testMultiRendererMetricGetsTheStyleSelect(): void
    {
        $form = $this->factory->create(EmbedType::class, [
            'renderer' => Renderer::Gauge,
        ], ['metric' => Metric::SystemRamUsage]);

        $this->assertTrue($form->has('renderer'));
        // Gauge does not support a range: no dependent fields.
        $this->assertFalse($form->has('range'));
    }

    public function testDependentRangeAppearsWhenRendererSupportsIt(): void
    {
        $form = $this->factory->create(EmbedType::class, [
            'renderer' => Renderer::Line,
        ], ['metric' => Metric::SystemRamUsage]);

        $this->assertTrue($form->has('range'));
        $this->assertTrue($form->has('chartConfig'));
    }

    public function testSubmitMapsValues(): void
    {
        $form = $this->factory->create(EmbedType::class, null, ['metric' => Metric::SystemRamUsage]);

        $form->submit([
            'renderer' => Renderer::Line->value,
            'range' => TimeRange::LAST_24_HOURS->value,
            'autoRefresh' => '1',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame(Renderer::Line, $form->get('renderer')->getData());
        $this->assertSame(TimeRange::LAST_24_HOURS, $form->get('range')->getData());
        $this->assertSame('1', $form->get('autoRefresh')->getData());
    }
}
