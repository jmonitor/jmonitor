<?php

declare(strict_types=1);

namespace App\Tests\Command\Demo;

use App\Command\Demo\DemoProvisionCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Guards against the "demo user logged out at every deploy" bug: the provision command runs on
 * every deploy, and re-hashing the password each time changes the stored hash (random salt).
 * User::isEqualTo() compares the password, so Symfony would treat the user as changed and drop
 * the session. The command must therefore be idempotent on the password.
 */
final class DemoProvisionCommandTest extends TestCase
{
    public function testExistingUserWithValidPasswordIsNotRehashed(): void
    {
        $existingHash = 'already-hashed-demo';
        $user = new User();
        $user->setEmail(DemoProvisionCommand::EMAIL);
        $user->setPassword($existingHash);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')
            ->with($user, DemoProvisionCommand::PASSWORD)
            ->willReturn(true);
        // The whole point: a valid password must NOT be re-hashed.
        $hasher->expects($this->never())->method('hashPassword');

        $command = new DemoProvisionCommand($this->createMock(EntityManagerInterface::class), $userRepository, $hasher);
        $command($this->io());

        self::assertSame($existingHash, $user->getPassword(), 'The stored password hash must stay stable across provisions.');
    }

    public function testNewUserGetsPasswordHashed(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        // A brand-new user has no stored hash, so isPasswordValid is never reached (short-circuit).
        $hasher->expects($this->never())->method('isPasswordValid');
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('freshly-hashed-demo');

        $createdUser = null;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$createdUser): void {
            if ($entity instanceof User) {
                $createdUser = $entity;
            }
        });

        $command = new DemoProvisionCommand($em, $userRepository, $hasher);
        $command($this->io());

        self::assertInstanceOf(User::class, $createdUser);
        self::assertSame('freshly-hashed-demo', $createdUser->getPassword());
    }

    private function io(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), new NullOutput());
    }
}
