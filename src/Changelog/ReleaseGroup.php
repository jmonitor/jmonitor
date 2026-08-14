<?php

declare(strict_types=1);

namespace App\Changelog;

readonly class ReleaseGroup
{
    /**
     * @param string $title   "Added", "Fixed"… empty when the release lists its entries without a heading
     * @param string[] $entries Markdown, one per changelog bullet
     */
    public function __construct(
        public string $title,
        public array $entries,
    ) {}
}
