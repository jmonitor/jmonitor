<?php

declare(strict_types=1);

namespace App\Tests\Demo\Generator;

use App\Demo\Generator\SymfonyGenerator;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Symfony\SymfonyConsumer;
use App\Metrics\Dto\Bag\Symfony\SchedulerTaskBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Validation;

class SymfonyGeneratorTest extends TestCase
{
    public function testGeneratesValidSymfonyMetrics(): void
    {
        $generator = new SymfonyGenerator();
        $this->assertSame(Consumer::SYMFONY, $generator->getConsumer());

        $metrics = $generator->generate(new DemoState(new ArrayAdapter()));

        $violations = Validation::createValidator()->validate(
            $metrics,
            (new SymfonyConsumer())->getConstraints(1),
        );

        $this->assertCount(0, $violations, (string) $violations);

        // Mirror the `messenger:stats --format=json` shape consumed by the dashboard:
        // countable transports carry a `count`, the rest are listed as uncountable.
        $messenger = $metrics['components']['messenger'];

        $this->assertSame(['count' => 0], $messenger['transports']['async']);
        $this->assertSame(['count' => 3], $messenger['transports']['failed']);
        $this->assertSame(['sync', 'scheduler_default'], $messenger['uncountable_transports']);

        // Two AsPeriodicTask commands (periodic + jitter) and one crontab command (no jitter).
        $triggers = array_column($metrics['components']['scheduler'], 'trigger');

        $this->assertContains('every 1 hour with 0-10 second jitter', $triggers);
        $this->assertContains('every 6 hours with 0-10 second jitter', $triggers);
        $this->assertContains('0 2 * * *', $triggers);

        // The bag must split "<interval> with <jitter> jitter" so the dashboard shows both parts.
        $periodic = new SchedulerTaskBag(['trigger' => 'every 1 hour with 0-10 second jitter']);
        $this->assertSame('every 1 hour', $periodic->trigger);
        $this->assertSame('0-10 second', $periodic->jitter);

        $cron = new SchedulerTaskBag(['trigger' => '0 2 * * *']);
        $this->assertSame('0 2 * * *', $cron->trigger);
        $this->assertNull($cron->jitter);
    }
}
