<?php

declare(strict_types=1);

namespace App\Tests\Alerting\AlertChecker;

use App\Alerting\AlertChecker\CustomTypeChecker;
use App\Alerting\AlertMetric;
use App\Entity\Alert;
use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class CustomTypeCheckerTest extends TestCase
{
    /**
     * @param array<string, mixed> $flexRecipes
     */
    private function makeSymfonyBag(array $flexRecipes): SymfonyBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::SYMFONY,
            1,
            ['components' => ['flex_recipes' => $flexRecipes]],
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(SymfonyBag::class, $bag);

        return $bag;
    }

    private function makeAlert(): Alert
    {
        $alert = new Alert();
        $alert->setAlertMetric(AlertMetric::SymfonyOutdatedFlexRecipes);

        return $alert;
    }

    public function testTriggersWhenRecipesAreOutdated(): void
    {
        $checker = new CustomTypeChecker();
        $alert = $this->makeAlert();
        $bag = $this->makeSymfonyBag([
            'up_to_date' => false,
            'outdated_recipes' => ['symfony/framework-bundle', 'symfony/console'],
        ]);

        $spotted = $checker->check($alert, $bag);

        $this->assertNotNull($spotted);
        $this->assertSame(2, $spotted->getValue());
        $this->assertSame(['symfony/framework-bundle', 'symfony/console'], $spotted->getDetails());
    }

    public function testDoesNotTriggerWhenUpToDate(): void
    {
        $checker = new CustomTypeChecker();

        $spotted = $checker->check($this->makeAlert(), $this->makeSymfonyBag(['up_to_date' => true]));

        $this->assertNull($spotted);
    }

    public function testDoesNotTriggerWhenDataMissing(): void
    {
        $checker = new CustomTypeChecker();

        $spotted = $checker->check($this->makeAlert(), $this->makeSymfonyBag([]));

        $this->assertNull($spotted);
    }
}
