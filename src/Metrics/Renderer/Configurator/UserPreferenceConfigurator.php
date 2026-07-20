<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Configurator;

use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Applies per-user rendering overrides.
 * Placeholder: user preferences don't exist yet, supports() always returns false.
 */
#[AsTaggedItem(priority: -50)] // runs last, so it has the final say
class UserPreferenceConfigurator implements MetricRendererOptionsConfiguratorInterface
{
    public function supports(RendererOptionsInterface $options, AbstractDto $dto): bool
    {
        return false;
    }

    public function configure(RendererOptionsInterface $options, AbstractDto $dto): void {}
}
