<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\Mysql\InfoSchemaGenerator;
use App\Demo\Generator\Mysql\QueriesCountGenerator;
use App\Demo\Generator\Mysql\SlowQueriesGenerator;
use App\Demo\Generator\Mysql\StatusGenerator;
use App\Demo\Generator\Mysql\VariablesGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Mysql\MysqlInformationSchemaConsumer;
use App\Metrics\Consumer\Mysql\QueriesCountConsumer;
use App\Metrics\Consumer\Mysql\SlowQueriesConsumer;
use App\Metrics\Consumer\Mysql\StatusConsumer;
use App\Metrics\Consumer\Mysql\VariablesConsumer;
use App\Metrics\DeltaCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class MysqlGeneratorsTest extends TestCase
{
    private DemoState $state;

    protected function setUp(): void
    {
        $this->state = new DemoState(new ArrayAdapter());
    }

    public function testStatus(): void
    {
        $this->assertValid(
            (new StatusGenerator())->generate($this->state),
            (new StatusConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(1),
        );
    }

    public function testVariables(): void
    {
        $this->assertValid((new VariablesGenerator())->generate($this->state), (new VariablesConsumer())->getConstraints(1));
    }

    public function testQueriesCount(): void
    {
        $this->assertValid((new QueriesCountGenerator())->generate($this->state), (new QueriesCountConsumer())->getConstraints(1));
    }

    public function testSlowQueries(): void
    {
        $this->assertValid((new SlowQueriesGenerator())->generate($this->state), (new SlowQueriesConsumer())->getConstraints(1));
    }

    public function testInfoSchema(): void
    {
        $generator = new InfoSchemaGenerator();
        $this->assertSame(Consumer::MYSQL_INFO_SCHEMA, $generator->getConsumer());
        $this->assertValid(
            $generator->generate($this->state),
            (new MysqlInformationSchemaConsumer())->getConstraints(1),
        );
    }

    private function assertValid(array $metrics, mixed $constraints): void
    {
        $violations = Validation::createValidator()->validate($metrics, $constraints);
        $this->assertCount(0, $violations, (string) $violations);
    }
}
