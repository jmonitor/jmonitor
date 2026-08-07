<?php

declare(strict_types=1);

namespace App\Tests\Security\Registration;

use App\Plan\Edition;
use App\Security\Registration\RegistrationGate;
use PHPUnit\Framework\TestCase;

class RegistrationGateTest extends TestCase
{
    public function testCloudAllowsRegistrationWithoutInvitation(): void
    {
        $this->assertTrue(new RegistrationGate(Edition::CLOUD)->isOpen());
    }

    public function testSelfHostedIsInviteOnly(): void
    {
        $this->assertFalse(new RegistrationGate(Edition::SELF_HOSTED)->isOpen());
    }
}
