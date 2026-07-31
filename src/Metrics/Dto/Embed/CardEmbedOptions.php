<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Embed;

/**
 * Card-level presentation options of an embed, independent of the renderer.
 */
final readonly class CardEmbedOptions
{
    public function __construct(
        public bool $showProjectName = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(showProjectName: (bool) ($data['showProjectName'] ?? false));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter(['showProjectName' => $this->showProjectName]);
    }
}
