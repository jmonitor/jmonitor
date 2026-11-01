<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Jmonitor\Collector\Cloudflare\CloudflareCollector;
use Jmonitor\Jmonitor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Drops the Cloudflare self-monitoring collector while CLOUDFLARE_API_TOKEN or CLOUDFLARE_ZONE is empty,
 * so an instance that does not use Cloudflare does not report a failing collector on every collect.
 *
 * The variables are read at compile time: a change takes effect on the next cache warmup.
 */
final class OptionalCloudflareCollectorPass implements CompilerPassInterface
{
    private const array REQUIRED_ENV = ['CLOUDFLARE_API_TOKEN', 'CLOUDFLARE_ZONE'];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CloudflareCollector::class) || $this->isConfigured($container)) {
            return;
        }

        $container->removeDefinition(CloudflareCollector::class);

        if (!$container->hasDefinition(Jmonitor::class)) {
            return;
        }

        $jmonitor = $container->getDefinition(Jmonitor::class);
        $jmonitor->setMethodCalls(array_values(array_filter(
            $jmonitor->getMethodCalls(),
            static fn(array $call): bool => !(($call[1][0] ?? null) instanceof Reference && (string) $call[1][0] === CloudflareCollector::class),
        )));
    }

    private function isConfigured(ContainerBuilder $container): bool
    {
        foreach (self::REQUIRED_ENV as $name) {
            if (trim((string) $container->resolveEnvPlaceholders('%env(default::' . $name . ')%', true)) === '') {
                return false;
            }
        }

        return true;
    }
}
