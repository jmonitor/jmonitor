<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Chart\Dto\GaugeChartConfiguration;
use App\Form\Embed\GaugeEmbedOptionsType;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class GaugeEmbedOptionsTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    public function testItExposesAnAspectRatioAndNoRange(): void
    {
        $form = $this->factory->create(GaugeEmbedOptionsType::class, null, $this->options());

        $this->assertTrue($form->has('aspectRatio'));
        $this->assertFalse($form->has('range'));
    }

    public function testSubmittingTheDefaultStoresNull(): void
    {
        $form = $this->factory->create(GaugeEmbedOptionsType::class, null, $this->options());

        $form->submit(['aspectRatio' => '1.7']);

        $data = $form->getData();
        $this->assertInstanceOf(GaugeEmbedOptions::class, $data);
        $this->assertNull($data->aspectRatio);
    }

    public function testSubmittingAnotherValueStoresIt(): void
    {
        $form = $this->factory->create(GaugeEmbedOptionsType::class, null, $this->options());

        $form->submit(['aspectRatio' => '1.2']);

        $data = $form->getData();
        $this->assertInstanceOf(GaugeEmbedOptions::class, $data);
        $this->assertSame(1.2, $data->aspectRatio);
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['defaults' => new GaugeChartConfiguration()];
    }
}
