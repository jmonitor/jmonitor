<?php

declare(strict_types=1);

namespace App\Controller\Attribute;

/**
 * Hydrates an EmbedDto controller argument from a query parameter,
 * through the same EmbedDto::fromArray() the stored config uses.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class MapEmbedDto
{
    public function __construct(
        public string $key = 'embed',
    ) {}
}
