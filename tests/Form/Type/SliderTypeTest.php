<?php

declare(strict_types=1);

namespace App\Tests\Form\Type;

use App\Form\Type\SliderType;
use Symfony\Component\Form\Test\TypeTestCase;

class SliderTypeTest extends TypeTestCase
{
    public function testNullModelValueIsDisplayedAsTheDefault(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        // RangeType has no view transformer, so the view data is the normalised float.
        $this->assertEquals(2.8, $form->getViewData());
    }

    public function testSubmittingTheDefaultStoresNull(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        $form->submit('2.8');

        $this->assertNull($form->getData());
    }

    public function testSubmittingAnotherValueStoresIt(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        $form->submit('4');

        $this->assertSame(4.0, $form->getData());
    }

    // A slider anchored on the min/step grid can only emit multiples of the step from the
    // default, so a value exactly half a step away can't come from the control itself — a
    // hand-crafted request at that boundary is stored as sent, not silently rewritten to null.
    public function testSubmittingExactlyHalfAStepAboveTheDefaultStoresIt(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        $form->submit('2.85');

        $this->assertSame(2.85, $form->getData());
    }

    public function testSubmittingExactlyHalfAStepBelowTheDefaultStoresIt(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        $form->submit('2.75');

        $this->assertSame(2.75, $form->getData());
    }

    public function testAnEmptySubmissionStoresNull(): void
    {
        $form = $this->factory->create(SliderType::class, null, $this->options());

        $form->submit('');

        $this->assertNull($form->getData());
    }

    public function testTheDefaultIsExposedToTheView(): void
    {
        $view = $this->factory->create(SliderType::class, null, $this->options())->createView();

        $this->assertSame(2.8, $view->vars['default']);
        $this->assertSame(0.5, $view->vars['attr']['min']);
        $this->assertSame(8.0, $view->vars['attr']['max']);
        $this->assertSame(0.1, $view->vars['attr']['step']);
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['default' => 2.8, 'min' => 0.5, 'max' => 8.0, 'step' => 0.1];
    }
}
