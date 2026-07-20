<?php

declare(strict_types=1);

namespace App\EventListener\Doctrine;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * In worker mode, the Doctrine connection stays open.
 * This can lead to "mysql server has gone away" errors, especially since Clever Cloud may also
 * close long-lived connections at its proxy's discretion.
 * So we close it at the start of each request.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 1000)]
readonly class PingConnectionListener
{
    public function __construct(private Connection $connection) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
