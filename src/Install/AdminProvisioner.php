<?php

declare(strict_types=1);

namespace App\Install;

use App\Entity\Enums\Role;
use App\Entity\Enums\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates the initial admin account of a self-hosted instance (app:install).
 * Idempotent: a no-op as soon as ANY admin exists — further accounts are
 * managed from the app itself.
 */
readonly class AdminProvisioner
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * @return User|null the created (or promoted) admin, null when an admin already exists
     *
     * @throws \RuntimeException when no admin exists and credentials are not provided
     */
    public function provision(string $email, string $password): ?User
    {
        if ($this->userRepository->findOneBy(['role' => Role::ROLE_ADMIN]) !== null) {
            return null;
        }

        if ($email === '' || $password === '') {
            throw new \RuntimeException('No admin account exists yet: set the ADMIN_EMAIL and ADMIN_PASSWORD environment variables.');
        }

        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]) ?? new User();
        $user->setEmail($email);
        $user->setRole(Role::ROLE_ADMIN);
        $user->setStatus(UserStatus::ACTIVE);

        // Same guard as DemoProvisionCommand: hashers salt randomly, so re-hashing
        // at every start would change the stored hash and invalidate sessions.
        if ($user->getPassword() === null || !$this->passwordHasher->isPasswordValid($user, $password)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
