<?php

declare(strict_types=1);

namespace App\Twig\Meta;

use Twig\Attribute\AsTwigFunction;

readonly class MetaExtension
{
    public function __construct(private Metas $metas) {}

    #[AsTwigFunction('metas')]
    public function getMeta(): Metas
    {
        return $this->metas;
    }
}
