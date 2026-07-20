<?php

declare(strict_types=1);

namespace App\Menu;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AutoconfigureTag('app.menu')]
interface MenuInterface
{
    /**
     * @param mixed[] $options
     * @return MenuLink[]
     */
    public function getLinks(array $options = []): iterable;

    public function getDefaultTemplate(): ?string;

    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @param mixed[] $options
     */
    public function getCacheKey(array $options): ?string;
}
