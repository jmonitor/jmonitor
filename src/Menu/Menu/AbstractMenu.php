<?php

declare(strict_types=1);

namespace App\Menu\Menu;

use App\Menu\MenuInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractMenu implements MenuInterface
{
    public function getDefaultTemplate(): ?string
    {
        return null;
    }

    public function configureOptions(OptionsResolver $resolver): void {}

    public function getCacheKey(array $options): ?string
    {
        return null;
    }
}
