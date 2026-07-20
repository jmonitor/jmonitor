<?php

declare(strict_types=1);

namespace App\Tests\Install;

use App\Entity\Enums\Role;
use App\Entity\Enums\UserStatus;
use App\Entity\User;
use App\Install\AdminProvisioner;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * The initial-admin provisioning runs at EVERY container start (entrypoint):
 * it must be a no-op once any admin exists, and must never re-hash a valid
 * password (random salt => new hash => sessions invalidated, same bug guarded
 * in DemoProvisionCommandTest).
 */
final class AdminProvisionerTest extends TestCase
{
    public function testCreatesAdminWhenNoneExists(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        // First lookup: any existing admin? Second lookup: user with that email?
        $userRepository->method('findOneBy')->willReturnCallback(
            static fn(array $criteria): ?User => null,
        );

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('hashPassword')->willReturn('hashed-password');

        $persisted = null;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted = $entity;
            });
        $em->expects($this->once())->method('flush');

        $provisioner = new AdminProvisioner($em, $userRepository, $hasher);
        $admin = $provisioner->provision('Admin@Example.com', 'secret');

        self::assertInstanceOf(User::class, $admin);
        self::assertSame($admin, $persisted);
        self::assertSame('admin@example.com', $admin->getEmail(), 'User::setEmail lowercases.');
        self::assertSame(Role::ROLE_ADMIN, $admin->getRole());
        self::assertSame(UserStatus::ACTIVE, $admin->getStatus());
        self::assertSame('hashed-password', $admin->getPassword());
    }

    public function testDoesNothingWhenAnAdminAlreadyExists(): void
    {
        $existingAdmin = new User();
        $existingAdmin->setRole(Role::ROLE_ADMIN);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturnCallback(
            static fn(array $criteria): ?User => ($criteria['role'] ?? null) === Role::ROLE_ADMIN ? $existingAdmin : null,
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $provisioner = new AdminProvisioner($em, $userRepository, $this->createMock(UserPasswordHasherInterface::class));

        self::assertNull($provisioner->provision('admin@example.com', 'secret'));
    }

    public function testThrowsWhenNoAdminExistsAndCredentialsAreMissing(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $provisioner = new AdminProvisioner($em, $userRepository, $this->createMock(UserPasswordHasherInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ADMIN_EMAIL/');
        $provisioner->provision('', '');
    }

    public function testPromotesExistingUserWithSameEmailWithoutRehashingValidPassword(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('admin@example.com');
        $existingUser->setPassword('already-hashed');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturnCallback(
            static fn(array $criteria): ?User => isset($criteria['email']) ? $existingUser : null,
        );

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->with($existingUser, 'secret')->willReturn(true);
        // The whole point: a valid password must NOT be re-hashed.
        $hasher->expects($this->never())->method('hashPassword');

        $em = $this->createMock(EntityManagerInterface::class);

        $provisioner = new AdminProvisioner($em, $userRepository, $hasher);
        $admin = $provisioner->provision('admin@example.com', 'secret');

        self::assertSame($existingUser, $admin);
        self::assertSame('already-hashed', $admin->getPassword());
        self::assertSame(Role::ROLE_ADMIN, $admin->getRole());
    }
}
