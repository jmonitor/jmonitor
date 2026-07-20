<?php

declare(strict_types=1);

namespace App\Plan;

/**
 * Edition of the application, driven by the APP_EDITION env var.
 * Registered as a service (factory in config/services.yaml): inject it
 * anywhere a behavior depends on the edition.
 */
enum Edition: string
{
    case CLOUD = 'cloud';
    case SELF_HOSTED = 'selfhosted';

    public function isCloud(): bool
    {
        return $this === self::CLOUD;
    }

    public function isSelfHosted(): bool
    {
        return $this === self::SELF_HOSTED;
    }
}
