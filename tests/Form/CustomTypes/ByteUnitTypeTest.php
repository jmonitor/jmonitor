<?php

declare(strict_types=1);

namespace App\Tests\Form\CustomTypes;

use App\Form\CustomTypes\ByteUnitType;
use Symfony\Component\Form\Test\TypeTestCase;

class ByteUnitTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'value' => 128,
            'unit' => 'MiB',
        ];

        $form = $this->factory->create(ByteUnitType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        // 128 MiB (binary) = 128 * 1024 * 1024 = 134217728
        $this->assertEquals(134217728, $form->getData());
    }

    public function testSubmitDecimalData(): void
    {
        $formData = [
            'value' => 1,
            'unit' => 'kB',
        ];

        $form = $this->factory->create(ByteUnitType::class, null, [
            'use_binary' => false,
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(1000, $form->getData());
    }

    public function testTransform(): void
    {
        $form = $this->factory->create(ByteUnitType::class);
        $form->setData(1048576); // 1 MiB

        $view = $form->createView();

        $this->assertEquals(1, $view->children['value']->vars['value']);
        $this->assertEquals('MiB', $view->children['unit']->vars['value']);
    }
}
