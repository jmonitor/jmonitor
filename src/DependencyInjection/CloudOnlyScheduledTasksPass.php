<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Command\Subscription\PurgeExpiredSubscriptionsCommand;
use App\Plan\Edition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Drops the cloud-only periodic tasks in the self-hosted edition. They already
 * return early there, but the Symfony page of a monitored instance lists its
 * scheduled tasks: without this, a self-hoster reads about expiring
 * subscriptions on an instance that has no billing.
 *
 * The edition is read at compile time, so a change to APP_EDITION only takes
 * effect on the next cache warmup — which is when the self-hosted image builds
 * its container anyway (entrypoint).
 */
final class CloudOnlyScheduledTasksPass implements CompilerPassInterface
{
    private const array CLOUD_ONLY_TASKS = [
        PurgeExpiredSubscriptionsCommand::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        $edition = Edition::tryFrom((string) $container->resolveEnvPlaceholders('%env(APP_EDITION)%', true));

        if (!$edition || $edition->isCloud()) {
            return;
        }

        foreach (self::CLOUD_ONLY_TASKS as $id) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->clearTag('scheduler.task');
            }
        }
    }
}
