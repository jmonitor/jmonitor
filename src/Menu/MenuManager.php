<?php

declare(strict_types=1);

namespace App\Menu;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class MenuManager
{
    private array $menus;

    public function __construct(#[AutowireIterator('app.menu', 'index')] iterable $menus)
    {
        $this->menus = $menus instanceof \Traversable ? iterator_to_array($menus) : $menus;
    }

    public function getMenu(string $name): MenuInterface
    {
        return $this->menus[$name];
    }
}
