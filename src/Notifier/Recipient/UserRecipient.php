<?php

declare(strict_types=1);

namespace App\Notifier\Recipient;

use App\Entity\User;
use Symfony\Component\Notifier\Recipient\Recipient;

/**
 * A recipient built from a User entity
 */
class UserRecipient extends Recipient
{
    private readonly User $user;

    public function __construct(User $user)
    {
        parent::__construct($user->getEmail());

        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
