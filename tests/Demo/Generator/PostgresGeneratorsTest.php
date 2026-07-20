<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\Postgres\ActivityGenerator;
use App\Demo\Generator\Postgres\DatabaseGenerator;
use App\Demo\Generator\Postgres\SettingsGenerator;
use App\Demo\Generator\Postgres\SlowQueriesGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Postgres\PostgresActivityConsumer;
use App\Metrics\Consumer\Postgres\PostgresDatabaseConsumer;
use App\Metrics\Consumer\Postgres\PostgresSettingsConsumer;
use App\Metrics\Consumer\Postgres\PostgresSlowQueriesConsumer;
use App\Metrics\DeltaCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class PostgresGeneratorsTest extends TestCase
{
    private DemoState $state;

    protected function setUp(): void
    {
        $this->state = new DemoState(new ArrayAdapter());
    }

    public function testSettings(): void
    {
        $this->assertValid((new SettingsGenerator())->generate($this->state), (new PostgresSettingsConsumer())->getConstraints(1));
    }

    public function testActivity(): void
    {
        $consumer = new PostgresActivityConsumer($this->createMock(DeltaCalculator::class));
        $this->assertValid((new ActivityGenerator())->generate($this->state), $consumer->getConstraints(1));
    }

    public function testDatabase(): void
    {
        $this->assertValid((new DatabaseGenerator())->generate($this->state), (new PostgresDatabaseConsumer())->getConstraints(1));
    }

    public function testSlowQueries(): void
    {
        $this->assertValid((new SlowQueriesGenerator())->generate($this->state), (new PostgresSlowQueriesConsumer())->getConstraints(1));
    }

    private function assertValid(array $metrics, $constraints): void
    {
        $violations = Validation::createValidator()->validate($metrics, $constraints);
        $this->assertCount(0, $violations, (string) $violations);
    }
}
