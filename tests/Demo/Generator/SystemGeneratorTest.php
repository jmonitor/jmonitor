<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\SystemGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\System\SystemConsumer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class SystemGeneratorTest extends TestCase
{
    public function testGeneratesMetricsThatPassConsumerConstraints(): void
    {
        $generator = new SystemGenerator();
        $this->assertSame(Consumer::SYSTEM, $generator->getConsumer());

        $metrics = $generator->generate(new DemoState(new ArrayAdapter()));

        $violations = Validation::createValidator()->validate(
            $metrics,
            (new SystemConsumer())->getConstraints(1),
        );

        $this->assertCount(0, $violations, (string) $violations);
    }
}
