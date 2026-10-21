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
use App\Metrics\Consumer\Consumer;
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
            (new ApacheConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(Consumer::APACHE->latestVersion()),
        );
    }

    public function testApacheDurationFollowsAccesses(): void
    {
        $generator = new ApacheGenerator();
        $first = $generator->generate($this->state);
        $second = $generator->generate($this->state);

        $averageMs = ($second['total_duration'] - $first['total_duration']) / ($second['total_accesses'] - $first['total_accesses']);

        $this->assertGreaterThanOrEqual(20, $averageMs);
        $this->assertLessThanOrEqual(400, $averageMs);
    }

    public function testNginx(): void
    {
        $this->assertValid(
            (new NginxGenerator())->generate($this->state),
            (new NginxConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(Consumer::NGINX->latestVersion()),
        );
    }

    public function testCaddy(): void
    {
        $this->assertValid(
            (new CaddyGenerator())->generate($this->state),
            (new CaddyConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(Consumer::CADDY->latestVersion()),
        );
    }

    public function testFrankenPhp(): void
    {
        $this->assertValid(
            (new FrankenPhpGenerator())->generate($this->state),
            (new FrankenPhpConsumer(
                $this->createMock(DeltaCalculator::class),
                new PlanResolver(Edition::CLOUD),
            ))->getConstraints(Consumer::FRANKENPHP->latestVersion()),
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
