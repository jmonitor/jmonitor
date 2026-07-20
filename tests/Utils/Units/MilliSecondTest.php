<?php

declare(strict_types=1);

namespace App\Tests\Utils\Units;

use App\Utils\Units\MilliSecond;
use PHPUnit\Framework\TestCase;

class MilliSecondTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $ms = new MilliSecond(100);
        $this->assertEquals(100, $ms->value());
    }

    public function test_it_can_convert_automatically(): void
    {
        $ms = new MilliSecond(500);
        $this->assertEquals(500, $ms->getFinalValue());
        $this->assertEquals('ms', $ms->getUnit());

        $ms = new MilliSecond(1500);
        $this->assertEquals(1.5, $ms->getFinalValue());
        $this->assertEquals('s', $ms->getUnit());

        $ms = new MilliSecond(120000);
        $this->assertEquals(2, $ms->getFinalValue());
        $this->assertEquals('m', $ms->getUnit());

        $ms = new MilliSecond(7200000);
        $this->assertEquals(2, $ms->getFinalValue());
        $this->assertEquals('h', $ms->getUnit());
    }

    public function test_it_can_convert_to_specific_unit(): void
    {
        $ms = (new MilliSecond(3600000))->to('s');
        $this->assertEquals(3600, $ms->getFinalValue());
        $this->assertEquals('s', $ms->getUnit());

        $ms = (new MilliSecond(60000))->to('ms');
        $this->assertEquals(60000, $ms->getFinalValue());
        $this->assertEquals('ms', $ms->getUnit());
    }

    public function test_it_throws_exception_for_invalid_unit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MilliSecond(1000))->to('invalid');
    }

    public function test_it_formats_correctly(): void
    {
        $ms = new MilliSecond(500);
        $this->assertEquals('500 <span class="unit">ms</span>', $ms->format());
        $this->assertEquals('500 ms', $ms->format(includeHtml: false));

        $ms = new MilliSecond(1500);
        $this->assertEquals('1.50 <span class="unit">s</span>', $ms->format());
        $this->assertEquals('1.50 s', $ms->format(includeHtml: false));

        $this->assertEquals('1.500 <span class="unit">s</span>', $ms->format('%.3f %s'));
        $this->assertEquals('1.500 s', $ms->format('%.3f %s', false));
    }

    public function test_it_handles_string_conversion(): void
    {
        $ms = new MilliSecond(1000);
        $this->assertEquals('1 <span class="unit">s</span>', (string) $ms);
    }
}
