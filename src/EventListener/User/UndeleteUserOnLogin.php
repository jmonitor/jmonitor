<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * When a user logs in interactively, if the account is soft-deleted (deletedAt not null),
 * cancel the deletion and inform the user with a flash message.
 */
#[AsEventListener]
readonly class UndeleteUserOnLogin
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        if ($event->getFirewallName() !== 'main') {
            return;
        }

        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->getDeletedAt() === null) {
            return;
        }

        $user->setDeletedAt(null);
        $this->em->flush();

        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', 'Account reactivated.');
        }
    }
}
