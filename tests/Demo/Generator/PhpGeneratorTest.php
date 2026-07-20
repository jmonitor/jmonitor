<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\PhpGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Php\PhpConsumer;
use App\Metrics\DeltaCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class PhpGeneratorTest extends TestCase
{
    public function testGeneratesValidPhpMetrics(): void
    {
        $generator = new PhpGenerator();
        $this->assertSame(Consumer::PHP, $generator->getConsumer());

        $metrics = $generator->generate(new DemoState(new ArrayAdapter()));

        $violations = Validation::createValidator()->validate(
            $metrics,
            (new PhpConsumer($this->createMock(DeltaCalculator::class)))->getConstraints(1),
        );

        $this->assertCount(0, $violations, (string) $violations);
    }
}
