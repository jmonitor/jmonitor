<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Form\Embed\ChartConfigType;
use App\Metrics\Renderer;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ChartConfigTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testLineRendererGetsAspectRatioField(): void
    {
        $form = $this->factory->create(ChartConfigType::class, null, ['renderer' => Renderer::Line]);

        $this->assertTrue($form->has('aspectRatio'));
    }

    public function testBarRendererGetsAspectRatioField(): void
    {
        $form = $this->factory->create(ChartConfigType::class, null, ['renderer' => Renderer::Bar]);

        $this->assertTrue($form->has('aspectRatio'));
    }

    public function testGaugeRendererGetsEmptyForm(): void
    {
        $form = $this->factory->create(ChartConfigType::class, null, ['renderer' => Renderer::Gauge]);

        $this->assertCount(0, $form);
    }

    public function testUnsupportedRendererThrows(): void
    {
        $this->expectException(\LogicException::class);

        $this->factory->create(ChartConfigType::class, null, ['renderer' => Renderer::Basic]);
    }
}
