<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\CloudOnlyEnvVarProcessor;
use App\Plan\Edition;
use PHPUnit\Framework\TestCase;

final class CloudOnlyEnvVarProcessorTest extends TestCase
{
    public function testResolvesTheInnerVarOnCloud(): void
    {
        $processor = new CloudOnlyEnvVarProcessor(Edition::CLOUD);

        $value = $processor->getEnv('cloud_only', 'SENTRY_DSN', static fn(string $name): string => 'https://sentry.example/' . $name);

        self::assertSame('https://sentry.example/SENTRY_DSN', $value);
    }

    public function testResolvesToEmptyStringOnSelfHosted(): void
    {
        $processor = new CloudOnlyEnvVarProcessor(Edition::SELF_HOSTED);

        $value = $processor->getEnv('cloud_only', 'SENTRY_DSN', static function (string $name): string {
            self::fail('The inner env var must not be resolved on self-hosted.');
        });

        self::assertSame('', $value);
    }
}
