<?php

namespace App\Tests\Chart\Units;

use App\Chart\Units\Bytes;
use PHPUnit\Framework\TestCase;

class BytesTest extends TestCase
{
    public function test_it_can_be_parsed(): void
    {
        $bytes = Bytes::parse('1 KiB');
        $this->assertEquals(1024, $bytes->value());
    }

    public function test_it_can_convert_automatically(): void
    {
        $bytes = new Bytes(2048);
        $this->assertEquals(2, $bytes->getFinalValue());
        $this->assertEquals('KiB', $bytes->getUnit());
        $this->assertEquals(1024, $bytes->getFactor());
    }

    public function test_it_can_convert_to_specific_unit(): void
    {
        $bytes = (new Bytes(1024 * 1024))->to('KiB');
        $this->assertEquals(1024, $bytes->getFinalValue());
        $this->assertEquals('KiB', $bytes->getUnit());
        $this->assertEquals(1024, $bytes->getFactor());
    }

    public function test_it_handles_decimal_system(): void
    {
        $bytes = (new Bytes(2000))->asDecimal();
        $this->assertEquals(2, $bytes->getFinalValue());
        $this->assertEquals('kB', $bytes->getUnit());
        $this->assertEquals(1000, $bytes->getFactor());
    }

    public function test_it_formats_correctly(): void
    {
        $bytes = new Bytes(2048);
        $this->assertEquals('2 <span class="unit">KiB</span>', $bytes->format());

        $bytes = new Bytes(1536);
        $this->assertEquals('1.50 <span class="unit">KiB</span>', $bytes->format());

        $this->assertEquals('1.500 <span class="unit">KiB</span>', $bytes->format('%.3f %s'));
    }
}
