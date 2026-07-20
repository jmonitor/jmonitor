<?php

declare(strict_types=1);

namespace App\Tests\Form\CustomTypes;

use App\Form\CustomTypes\MsUnitType;
use Symfony\Component\Form\Test\TypeTestCase;

class MsUnitTypeTest extends TypeTestCase
{
    public function testSubmitValidDataMs(): void
    {
        $formData = [
            'value' => 500,
            'unit' => 'ms',
        ];

        $form = $this->factory->create(MsUnitType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(500, $form->getData());
    }

    public function testSubmitValidDataSeconds(): void
    {
        $formData = [
            'value' => 1.5,
            'unit' => 's',
        ];

        $form = $this->factory->create(MsUnitType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(1500, $form->getData());
    }

    public function testTransform(): void
    {
        $form = $this->factory->create(MsUnitType::class);
        $form->setData(2500); // 2.5 s

        $view = $form->createView();

        $this->assertEquals(2.5, (float) str_replace(',', '.', (string) $view->children['value']->vars['value']));
        $this->assertEquals('s', $view->children['unit']->vars['value']);
    }

    public function testTransformMs(): void
    {
        $form = $this->factory->create(MsUnitType::class);
        $form->setData(500); // 500 ms

        $view = $form->createView();

        $this->assertEquals(500, $view->children['value']->vars['value']);
        $this->assertEquals('ms', $view->children['unit']->vars['value']);
    }
}
