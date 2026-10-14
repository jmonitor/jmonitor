<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Denies iframe embedding app-wide (clickjacking) except on the public embed route,
 * whose whole purpose is to be framed by third-party pages and widgets, and on the public
 * metric help, framed by third-party back-offices.
 */
#[AsEventListener(event: ResponseEvent::class, method: 'onResponse')]
readonly class FrameAncestorsListener
{
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') === 'embed.public') {
            return;
        }

        $response = $event->getResponse();

        if ($response->headers->has('Content-Security-Policy')) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') === 'help.metric.public') {
            $response->headers->set('Content-Security-Policy', 'frame-ancestors *');

            return;
        }

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
    }
}
