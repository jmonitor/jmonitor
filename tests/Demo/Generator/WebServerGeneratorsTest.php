<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\ApacheGenerator;
use App\Demo\Generator\CaddyGenerator;
use App\Demo\Generator\FrankenPhpGenerator;
use App\Demo\Generator\NginxGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Apache\ApacheConsumer;
use App\Metrics\Consumer\Caddy\CaddyConsumer;
use App\Metrics\Consumer\FrankenPhp\FrankenPhpConsumer;
use App\Metrics\Consumer\Nginx\NginxConsumer;
use App\Metrics\DeltaCalculator;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class WebServerGeneratorsTest extends TestCase
{
    private DemoState $state;

    protected function setUp(): void
    {
        $this->state = new DemoState(new ArrayAdapter());
    }

    public function testApache(): void
    {
        $this->assertValid(
            (new ApacheGenerator())->generate($this->state),
            (new ApacheConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(1),
        );
    }

    public function testNginx(): void
    {
        $this->assertValid(
            (new NginxGenerator())->generate($this->state),
            (new NginxConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(1),
        );
    }

    public function testCaddy(): void
    {
        $this->assertValid(
            (new CaddyGenerator())->generate($this->state),
            (new CaddyConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(1),
        );
    }

    public function testFrankenPhp(): void
    {
        $this->assertValid(
            (new FrankenPhpGenerator())->generate($this->state),
            (new FrankenPhpConsumer(
                $this->createMock(DeltaCalculator::class),
                new PlanResolver(Edition::CLOUD),
            ))->getConstraints(1),
        );
    }

    /**
     * @param array<mixed> $metrics
     * @param mixed        $constraints
     */
    private function assertValid(array $metrics, mixed $constraints): void
    {
        $violations = Validation::createValidator()->validate($metrics, $constraints);
        $this->assertCount(0, $violations, (string) $violations);
    }
}
