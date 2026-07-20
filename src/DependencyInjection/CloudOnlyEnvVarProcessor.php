<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Plan\Edition;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * `%env(cloud_only:SOME_VAR)%` resolves to the inner env var on the cloud
 * edition, and to an empty string on self-hosted — for services that only the
 * JMonitor team should operate (e.g. Sentry: self-hosters are not the
 * developers of the app, its errors are not theirs to triage), where the
 * codebase convention "empty value = disabled" already applies.
 */
final class CloudOnlyEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private readonly Edition $edition,
    ) {}

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        if ($this->edition->isSelfHosted()) {
            return '';
        }

        return (string) $getEnv($name);
    }

    public static function getProvidedTypes(): array
    {
        return ['cloud_only' => 'string'];
    }
}
