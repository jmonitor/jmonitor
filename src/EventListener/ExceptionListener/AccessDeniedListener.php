<?php

declare(strict_types=1);

namespace App\EventListener\ExceptionListener;

use App\Entity\User;
use App\Project\ProjectContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Handles access denials for a member of the current project (e.g. a viewer hitting an admin action),
 * and for a user without ROLE_ADMIN reaching the admin host.
 */
readonly class AccessDeniedListener
{
    public function __construct(
        private Security $security,
        private ProjectContext $projectContext,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'APP_DOMAIN')]
        private string $domain,
    ) {}

    /**
     * Turbo-frame request (e.g. the "resume" button of an alert on the dashboard).
     *
     * Intercepted BEFORE the firewall's ExceptionListener (priority 1): otherwise a
     * "remember me" user (not fully authenticated) would have their token cancelled and be
     * redirected to /login instead of seeing the denial.
     */
    #[AsEventListener(priority: 5)]
    public function onTurboFrameAccessDenied(ExceptionEvent $event): void
    {
        if (!$this->isAccessDenied($event->getThrowable())) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->headers->has('Turbo-Frame') || !$this->isCurrentProjectMember()) {
            return;
        }

        $this->addDangerFlash($request, 'You are not allowed to do this.');

        $event->setResponse(new Response('', Response::HTTP_FORBIDDEN));
    }

    /**
     * Regular navigation: a member (viewer) who types a forbidden URL (e.g. project
     * settings) is sent back to the referer with a flash message, rather than to a 403 page.
     */
    #[AsEventListener]
    public function onNavigationAccessDenied(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof AccessDeniedHttpException) {
            return;
        }

        $request = $event->getRequest();

        if ($request->headers->has('Turbo-Frame')) {
            return;
        }

        if ($this->isDeniedAdminHost($request)) {
            $target = $this->urlGenerator->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);
        } elseif ($this->isCurrentProjectMember()) {
            $target = $this->sameHostReferer($request) ?? '/';
        } else {
            return;
        }

        $this->addDangerFlash($request, 'You are not allowed to access this page.');

        $event->setResponse(new RedirectResponse($target));
    }

    /**
     * A denial to answer off the admin host: redirecting a user who cannot enter it to another
     * of its URLs looped the browser.
     */
    private function isDeniedAdminHost(Request $request): bool
    {
        return $request->getHost() === 'admin.' . $this->domain
            && !$this->security->isGranted('ROLE_ADMIN');
    }

    /**
     * The referer is client-provided: only follow it back into the app.
     */
    private function sameHostReferer(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if ($referer === null || !str_starts_with($referer, $request->getSchemeAndHttpHost() . '/')) {
            return null;
        }

        return $referer;
    }

    private function isAccessDenied(\Throwable $throwable): bool
    {
        return $throwable instanceof AccessDeniedException || $throwable instanceof AccessDeniedHttpException;
    }

    private function isCurrentProjectMember(): bool
    {
        $project = $this->projectContext->getCurrentProject();
        $user = $this->security->getUser();

        return $project !== null
            && $user instanceof User
            && $user->getRoleInProject($project) !== null;
    }

    private function addDangerFlash(Request $request, string $message): void
    {
        /** @var FlashBagAwareSessionInterface $session */
        $session = $request->getSession();

        $session->getFlashBag()->add('danger', $message);
    }
}
