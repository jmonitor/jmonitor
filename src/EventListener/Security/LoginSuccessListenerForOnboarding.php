<?php

declare(strict_types=1);

namespace App\EventListener\Security;

use App\Entity\Enums\UserStatus;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * On login, redirects a user with "Onboarding" status to the onboarding page.
 */
#[AsEventListener]
readonly class LoginSuccessListenerForOnboarding
{
    public function __construct(
        private UrlGeneratorInterface $router,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User && $user->getStatus() === UserStatus::ONBOARDING) {
            $event->setResponse(new RedirectResponse($this->router->generate('dashboard.onboarding')));
        }
    }
}
