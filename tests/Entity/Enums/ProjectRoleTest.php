<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enums;

use App\Entity\Enums\ProjectRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProjectRoleTest extends TestCase
{
    /**
     * @return iterable<string, array{ProjectRole, ProjectRole, bool}>
     */
    public static function canManageProvider(): iterable
    {
        // Owner can manage everyone, including other owners.
        yield 'owner manages owner' => [ProjectRole::OWNER, ProjectRole::OWNER, true];
        yield 'owner manages admin' => [ProjectRole::OWNER, ProjectRole::ADMIN, true];
        yield 'owner manages viewer' => [ProjectRole::OWNER, ProjectRole::VIEWER, true];

        // Admin can only manage viewers, not other admins or owners.
        yield 'admin cannot manage owner' => [ProjectRole::ADMIN, ProjectRole::OWNER, false];
        yield 'admin cannot manage admin' => [ProjectRole::ADMIN, ProjectRole::ADMIN, false];
        yield 'admin manages viewer' => [ProjectRole::ADMIN, ProjectRole::VIEWER, true];

        // Viewer can manage no one.
        yield 'viewer cannot manage owner' => [ProjectRole::VIEWER, ProjectRole::OWNER, false];
        yield 'viewer cannot manage admin' => [ProjectRole::VIEWER, ProjectRole::ADMIN, false];
        yield 'viewer cannot manage viewer' => [ProjectRole::VIEWER, ProjectRole::VIEWER, false];
    }

    #[DataProvider('canManageProvider')]
    public function testCanManage(ProjectRole $actor, ProjectRole $target, bool $expected): void
    {
        $this->assertSame($expected, $actor->canManage($target));
    }
}
