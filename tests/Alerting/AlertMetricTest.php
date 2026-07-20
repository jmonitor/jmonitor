<?php

declare(strict_types=1);

namespace App\Tests\Alerting;

use App\Alerting\AlertMetric;
use App\Entity\Enums\AlertType;
use App\Entity\Enums\Component;
use PHPUnit\Framework\TestCase;

class AlertMetricTest extends TestCase
{
    public function testOutdatedFlexRecipesMapping(): void
    {
        $metric = AlertMetric::SymfonyOutdatedFlexRecipes;

        $this->assertSame('Outdated Flex recipes', $metric->label());
        $this->assertSame(AlertType::Custom, $metric->getType());
        $this->assertSame(Component::Symfony, $metric->component());
        $this->assertNull($metric->configBagClass());
        $this->assertNull($metric->configFormTypeClass());
        $this->assertNull($metric->unit());
    }
}
