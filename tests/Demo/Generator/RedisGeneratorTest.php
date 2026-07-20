<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\RedisGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Redis\RedisConsumer;
use App\Metrics\DeltaCalculator;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class RedisGeneratorTest extends TestCase
{
    public function testGeneratesValidRedisMetrics(): void
    {
        $generator = new RedisGenerator();
        $this->assertSame(Consumer::REDIS, $generator->getConsumer());

        $metrics = $generator->generate(new DemoState(new ArrayAdapter()));

        $violations = Validation::createValidator()->validate(
            $metrics,
            (new RedisConsumer(
                $this->createMock(DeltaCalculator::class),
                new PlanResolver(Edition::CLOUD),
            ))->getConstraints(1),
        );

        $this->assertCount(0, $violations, (string) $violations);
    }
}
