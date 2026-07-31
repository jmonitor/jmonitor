<?php

declare(strict_types=1);

namespace App\Tests\Form\Embed;

use App\Form\Embed\CardEmbedOptionsType;
use App\Metrics\Dto\Embed\CardEmbedOptions;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * EmbedDto::$card is non-nullable and MetricsController feeds it straight from
 * $form->get('card')->getData() — the empty_data closure is the only thing guaranteeing
 * that's never null.
 */
class CardEmbedOptionsTypeTest extends TypeTestCase
{
    public function testANullSubmissionYieldsAnObjectWithShowProjectNameFalse(): void
    {
        $form = $this->factory->create(CardEmbedOptionsType::class, null);

        $form->submit([]);

        $data = $form->getData();
        $this->assertInstanceOf(CardEmbedOptions::class, $data);
        $this->assertFalse($data->showProjectName);
    }

    public function testAPresetTrueValueRendersChecked(): void
    {
        $form = $this->factory->create(CardEmbedOptionsType::class, new CardEmbedOptions(true));

        $this->assertTrue($form->get('showProjectName')->getData());
    }

    public function testSubmittingEmptyOnAPresetTrueValueComesBackFalse(): void
    {
        $form = $this->factory->create(CardEmbedOptionsType::class, new CardEmbedOptions(true));

        $form->submit([]);

        $data = $form->getData();
        $this->assertInstanceOf(CardEmbedOptions::class, $data);
        $this->assertFalse($data->showProjectName);
    }
}
