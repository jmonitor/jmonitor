<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Cards
{
    /**
     * @var string[]
     */
    public array $titles = [];

    /**
     * Keep the active tab in the URL hash so it survives a page reload (auto-refresh).
     */
    public bool $memory = false;

    public function setTitle(string $title): void
    {
        $this->titles = [$title];
    }
}
