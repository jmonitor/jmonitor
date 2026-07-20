<?php

declare(strict_types=1);

namespace App\Tests\Plan;

use App\Plan\Edition;
use PHPUnit\Framework\TestCase;

class EditionTest extends TestCase
{
    public function testHelpers(): void
    {
        $this->assertTrue(Edition::CLOUD->isCloud());
        $this->assertFalse(Edition::CLOUD->isSelfHosted());
        $this->assertTrue(Edition::SELF_HOSTED->isSelfHosted());
        $this->assertFalse(Edition::SELF_HOSTED->isCloud());
    }

    public function testRejectsUnknownEdition(): void
    {
        $this->expectException(\ValueError::class);
        Edition::from('typo');
    }
}
