<?php

declare(strict_types=1);

namespace App\Logging;

use App\Plan\Edition;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;

/**
 * Builds the monolog handler for the "deprecation" channel: NullHandler in the
 * self-hosted edition, where a deprecation of the app's own dependencies is
 * noise nobody can act on, and would sit in `docker compose logs` between the
 * lines the install actually reports.
 */
final readonly class DeprecationHandlerFactory
{
    public function __construct(
        private Edition $edition,
    ) {}

    public function create(): HandlerInterface
    {
        if ($this->edition->isSelfHosted()) {
            return new NullHandler();
        }

        return new ErrorLogHandler();
    }
}
