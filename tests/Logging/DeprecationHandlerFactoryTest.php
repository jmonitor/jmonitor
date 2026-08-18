<?php

declare(strict_types=1);

namespace App\Tests\Logging;

use App\Logging\DeprecationHandlerFactory;
use App\Plan\Edition;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

final class DeprecationHandlerFactoryTest extends TestCase
{
    public function testDeprecationsAreLoggedInTheCloudEdition(): void
    {
        self::assertInstanceOf(ErrorLogHandler::class, (new DeprecationHandlerFactory(Edition::CLOUD))->create());
    }

    public function testDeprecationsAreDroppedInTheSelfHostedEdition(): void
    {
        self::assertInstanceOf(NullHandler::class, (new DeprecationHandlerFactory(Edition::SELF_HOSTED))->create());
    }
}
