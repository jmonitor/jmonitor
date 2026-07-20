<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum ProjectRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case VIEWER = 'viewer';

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isViewer(): bool
    {
        return $this === self::VIEWER;
    }

    public function isGrantedOwner(): bool
    {
        return $this->isOwner();
    }

    public function isGrantedAdmin(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    public function isLowerThan(self $role): bool
    {
        return $this->score() < $role->score();
    }

    public function isHigherThan(self $role): bool
    {
        return $this->score() > $role->score();
    }

    /**
     * Whether this role is allowed to manage (remove, change the role of, invite at) the given role.
     * Owners can manage everyone, including other owners. Admins can only manage viewers.
     */
    public function canManage(self $role): bool
    {
        return $this->isOwner() || $this->isHigherThan($role);
    }

    public function getUpgradable(): ?ProjectRole
    {
        return match ($this) {
            self::OWNER => null,
            self::ADMIN => self::OWNER,
            self::VIEWER => self::ADMIN,
        };
    }

    public function getDowngradable(): ?ProjectRole
    {
        return match ($this) {
            self::OWNER => self::ADMIN,
            self::ADMIN => self::VIEWER,
            self::VIEWER => null,
        };
    }

    public function restrictionsDescription(): string
    {
        return match ($this) {
            self::OWNER => 'No restrictions. Only owners can manage other owners and admins.',
            self::ADMIN => 'Admins cannot delete the project. They can manage members.',
            self::VIEWER => 'Viewers cannot edit project settings. They have read-only access.',
        };
    }

    private function score(): int
    {
        return match ($this) {
            self::OWNER => 3,
            self::ADMIN => 2,
            self::VIEWER => 1,
        };
    }
}
