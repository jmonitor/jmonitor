<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
readonly class LastConnectedAtListener
{
    public function __construct(private Security $security, private EntityManagerInterface $em) {}

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->security->getUser() instanceof User) {
            return;
        }

        if ($this->security->isGranted('IS_IMPERSONATOR')) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (date('Y-m-d') === $user->getLastConnectedDate()?->format('Y-m-d')) {
            return;
        }
        $user->setLastConnectedDate(new \DateTimeImmutable());

        $this->em->flush();
    }
}
