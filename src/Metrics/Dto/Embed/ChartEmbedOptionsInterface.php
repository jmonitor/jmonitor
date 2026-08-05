<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Embed;

use App\Metrics\Renderer\Options\RendererOptionsBuilderInterface;

/**
 * Chart-level presentation options of an embed, for one renderer family.
 * The object that holds the values is the object that applies them.
 */
interface ChartEmbedOptionsInterface extends RendererOptionsBuilderInterface
{
    /** @param array<string, mixed> $data stored/query shape, values may be strings */
    public static function fromArray(array $data): static;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
