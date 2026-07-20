<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Field on User
 * - mirrors the Symfony roles (but we don't need several of them, hence the simplification)
 */
enum Role: string implements TranslatableInterface
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::ROLE_USER => 'Member',
            self::ROLE_ADMIN => 'Administrator',
        };
    }

    // for easyadmin
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }

    public function isAdmin(): bool
    {
        return match ($this) {
            self::ROLE_ADMIN => true,
            self::ROLE_USER => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function eaChoices(): array
    {
        return [
            self::ROLE_USER->label() => self::ROLE_USER->value,
            self::ROLE_ADMIN->label() => self::ROLE_ADMIN->value,
        ];
    }
}
