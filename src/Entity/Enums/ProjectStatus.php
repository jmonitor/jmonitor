<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum ProjectStatus: string
{
    /**
     * Created, no metrics received yet.
     */
    case NEW = 'new';

    /**
     * Metrics have already been pushed.
     */
    case ACTIVE = 'active';

    public function isNew(): bool
    {
        return $this === self::NEW;

    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
