<?php

declare(strict_types=1);

namespace App\Tests\Assets;

use App\Entity\Enums\Component;
use App\Entity\Enums\Plan;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Icons are never fetched at runtime, and `ux:icons:lock` only sees the names written in templates.
 */
class IconFilesTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function iconNames(): iterable
    {
        foreach (Component::cases() as $component) {
            yield 'component ' . $component->name => [$component->icon()];
        }

        foreach (Plan::cases() as $plan) {
            yield 'plan ' . $plan->name => [$plan->icon()];
        }

        foreach (BadgeStyle::cases() as $style) {
            if ($style->getDefaultIcon() !== null) {
                yield 'badge ' . $style->name => [$style->getDefaultIcon()];
            }
        }
    }

    #[DataProvider('iconNames')]
    public function testIconFileIsCommitted(string $name): void
    {
        $this->assertFileExists(\dirname(__DIR__, 2) . '/assets/icons/' . str_replace(':', '/', $name) . '.svg');
    }
}
