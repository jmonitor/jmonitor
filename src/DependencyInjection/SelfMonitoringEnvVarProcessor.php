<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Entity\Project;
use App\Plan\Edition;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * `%env(selfmonitoring:JMONITOR_API_KEY)%` — self-hosted only: when the env
 * var is empty, falls back to the API key of the project provisioned by
 * app:install (see SelfMonitoringProvisioner). A non-empty var always wins;
 * '' means disabled (jmonitor:collect then exits without pushing).
 */
final class SelfMonitoringEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Edition $edition,
        #[Autowire(env: 'bool:SELF_MONITORING')]
        private readonly bool $selfMonitoringEnabled,
    ) {}

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $value = (string) $getEnv($name);
        if ($value !== '') {
            return $value;
        }

        if (!$this->edition->isSelfHosted() || !$this->selfMonitoringEnabled) {
            return '';
        }

        $apiKey = $this->connection->fetchOne(
            'SELECT api_key FROM project WHERE uuid = ?',
            [Uuid::fromString(Project::SELF_MONITORING_UUID)->toBinary()],
        );

        return is_string($apiKey) ? $apiKey : '';
    }

    public static function getProvidedTypes(): array
    {
        return ['selfmonitoring' => 'string'];
    }
}
