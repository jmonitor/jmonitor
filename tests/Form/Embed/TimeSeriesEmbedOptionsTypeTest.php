<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Form\Embed\TimeSeriesEmbedOptionsType;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class TimeSeriesEmbedOptionsTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    public function testItExposesRangeAndAspectRatio(): void
    {
        $form = $this->factory->create(TimeSeriesEmbedOptionsType::class, null, $this->options());

        $this->assertTrue($form->has('range'));
        $this->assertTrue($form->has('aspectRatio'));
    }

    public function testSubmittingTheDefaultAspectRatioStoresNull(): void
    {
        $form = $this->factory->create(TimeSeriesEmbedOptionsType::class, null, $this->options());

        $form->submit(['range' => 'last_1_hour', 'aspectRatio' => '2.8']);

        $data = $form->getData();
        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, $data);
        $this->assertNull($data->aspectRatio);
        $this->assertSame(TimeRange::LAST_1_HOUR, $data->range);
    }

    public function testSubmittingAnotherAspectRatioStoresIt(): void
    {
        $form = $this->factory->create(TimeSeriesEmbedOptionsType::class, null, $this->options());

        $form->submit(['range' => '', 'aspectRatio' => '4']);

        $data = $form->getData();
        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, $data);
        $this->assertSame(4.0, $data->aspectRatio);
        $this->assertNull($data->range);
    }

    public function testExistingDataIsMappedToTheFields(): void
    {
        $form = $this->factory->create(
            TimeSeriesEmbedOptionsType::class,
            new TimeSeriesEmbedOptions(TimeRange::LAST_24_HOURS, 4.0),
            $this->options(),
        );

        $this->assertSame(TimeRange::LAST_24_HOURS, $form->get('range')->getData());
        $this->assertSame(4.0, $form->get('aspectRatio')->getData());
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['defaults' => new TimeSeriesChartConfiguration()];
    }
}
