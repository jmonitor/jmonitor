<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Symfony;

use App\Metrics\Consumer\Symfony\SymfonyConsumer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class SymfonyConsumerTest extends TestCase
{
    /**
     * @param array<string, mixed> $task
     */
    #[DataProvider('provideValidSchedulerTasks')]
    public function testSchedulerTaskIsValid(array $task): void
    {
        $violations = Validation::createValidator()->validate(
            ['components' => ['scheduler' => [$task]]],
            new SymfonyConsumer()->getConstraints(1),
        );

        $this->assertCount(0, $violations, (string) $violations);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideValidSchedulerTasks(): iterable
    {
        yield 'bundle before 2.2' => [[
            'trigger' => 'every 1 hour',
            'command' => 'app:foo',
            'next_run' => 1_766_197_200,
            'description' => 'Sample description',
        ]];

        yield 'with arguments' => [[
            'trigger' => 'every 6 hours',
            'command' => 'app:foo',
            'arguments' => ['--apply', '--limit', '100'],
            'next_run' => 1_766_197_200,
            'description' => 'Sample description',
        ]];

        yield 'command not found' => [[
            'trigger' => 'every 6 hours',
            'command' => 'app:gone',
            'arguments' => [],
            'next_run' => 1_766_197_200,
            'description' => null,
        ]];
    }

    public function testSchedulerTaskArgumentsMustBeStrings(): void
    {
        $violations = Validation::createValidator()->validate(
            ['components' => ['scheduler' => [[
                'trigger' => 'every 6 hours',
                'command' => 'app:foo',
                'arguments' => [['nested']],
                'next_run' => 1_766_197_200,
            ]]]],
            new SymfonyConsumer()->getConstraints(1),
        );

        $this->assertCount(1, $violations);
        $this->assertSame('[components][scheduler][0][arguments][0]', $violations[0]->getPropertyPath());
    }
}
