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

    public function setTitle(string $title): void
    {
        $this->titles = [$title];
    }
}
