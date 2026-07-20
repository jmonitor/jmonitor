<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum UserStatus: string
{
    case ONBOARDING = 'onboarding';
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
