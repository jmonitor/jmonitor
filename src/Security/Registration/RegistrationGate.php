<?php

declare(strict_types=1);

namespace App\Security\Registration;

use App\Plan\Edition;

/**
 * Whether an account can be created without an invitation. Self-hosted instances are
 * invite-only: an instance exposed to the internet must not let anyone sign up.
 */
final readonly class RegistrationGate
{
    public function __construct(private Edition $edition) {}

    public function isOpen(): bool
    {
        return $this->edition->isCloud();
    }
}
