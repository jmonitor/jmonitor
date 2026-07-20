<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class FlashMessenger
{
    public function __construct(private readonly RequestStack $requestStack, private readonly Security $security) {}

    public function success(string $message): void
    {
        $this->addFlash('success', $message);
    }

    public function warning(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    public function error(string $message): void
    {
        $this->addFlash('danger', $message);
    }

    public function adminRedirectionNotice(string $reason): void
    {
        $message = \sprintf('<em>Admin message</em><div class="ms-2">You have been redirected because:<br><b>%s</b></div>', $reason);

        $this->adminWarning($message);
    }

    public function adminWarning(string $message): void
    {
        $this->adminFlash('warning', $message);
    }

    private function addFlash(string $type, string $message): void
    {
        $this->getFlashBag()?->add($type, $message);
    }

    private function adminFlash(string $type, string $message): void
    {
        if (!$this->security->isGranted('ROLE_EDITOR')) {
            return;
        }

        $this->addFlash($type, $message);
    }

    #[AsEventListener]
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // flash messages are passed as response headers since flashes are not available inside a turbo-frame
        // they are displayed by a JS controller (toast-header_controller.js)
        if (
            $event->isMainRequest()
            && $event->getRequest()->headers->has('turbo-frame')
            && !$response->isRedirection()
            && $event->getRequest()->isMethod('GET')
            && ($flashes = $this->getFlashBag()?->all())
        ) {
            $response->headers->set('X-toasts', json_encode($flashes));
        }
    }

    private function getFlashBag(): ?FlashBagInterface
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return null;
        }

        return $session->getFlashBag();
    }
}
