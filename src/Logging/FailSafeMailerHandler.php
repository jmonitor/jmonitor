<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * An error email that cannot be sent must not crash the process that logged the error.
 */
final class FailSafeMailerHandler extends HandlerWrapper
{
    public function handle(LogRecord $record): bool
    {
        try {
            return parent::handle($record);
        } catch (TransportExceptionInterface $e) {
            $this->reportFailure($e);

            return false;
        }
    }

    public function handleBatch(array $records): void
    {
        try {
            parent::handleBatch($records);
        } catch (TransportExceptionInterface $e) {
            $this->reportFailure($e);
        }
    }

    private function reportFailure(TransportExceptionInterface $e): void
    {
        error_log('Error email not sent: ' . $e->getMessage());
    }
}
