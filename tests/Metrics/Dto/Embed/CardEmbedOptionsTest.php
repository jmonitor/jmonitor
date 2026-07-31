<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Embed;

use App\Metrics\Dto\Embed\CardEmbedOptions;
use PHPUnit\Framework\TestCase;

class CardEmbedOptionsTest extends TestCase
{
    // toArray() filters on "!== null" like its chart-option siblings, not a bare array_filter()
    // that would also drop a future 0/''/0.0 option value — pin that false stays omitted.
    public function testToArrayOmitsShowProjectNameWhenFalse(): void
    {
        $this->assertSame([], new CardEmbedOptions(false)->toArray());
    }

    public function testToArrayKeepsShowProjectNameWhenTrue(): void
    {
        $this->assertSame(['showProjectName' => true], new CardEmbedOptions(true)->toArray());
    }
}
