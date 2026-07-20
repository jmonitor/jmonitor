<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Symfony;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class SymfonyBagTest extends TestCase
{
    public function testBag(): void
    {
        $data = [
            "env" => "dev",
            "debug" => true,
            "version" => "7.4.2",
            "bundles" => ["FrameworkBundle", "DoctrineBundle"],
            "project_dir" => "C:\\Users\\jonat\\www\\jmonitor",
            "cache_dir" => "C:\\Users\\jonat\\www\\jmonitor/var/cache/dev",
            "log_dir" => "C:\\Users\\jonat\\www\\jmonitor/var/log",
            "build_dir" => "C:\\Users\\jonat\\www\\jmonitor/var/cache/dev",
            "share_dir" => "C:\\Users\\jonat\\www\\jmonitor/var/share/dev",
            "charset" => "UTF-8",
            "components" => [
                "scheduler" => [
                    [
                        "trigger" => "every 15 seconds",
                        "command" => "jmonitor:collect",
                        "next_run" => 1767042907,
                        "description" => "Collect and send metrics to Jmonitor",
                    ],
                ],
                "flex_recipes" => [
                    "up_to_date" => true,
                ],
            ],
        ];

        $project = $this->createMock(Project::class);
        $bag = MetricBagDto::create(
            $project,
            Consumer::SYMFONY,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(SymfonyBag::class, $bag);
        /** @var SymfonyBag $bag */

        $this->assertEquals('dev', $bag->env);
        $this->assertTrue($bag->debug);
        $this->assertEquals('7.4.2', $bag->symfonyVersion);
        $this->assertEquals(["FrameworkBundle", "DoctrineBundle"], $bag->bundles);
        $this->assertEquals('C:\\Users\\jonat\\www\\jmonitor', $bag->projectDir);

        $this->assertEquals('C:\\Users\\jonat\\www\\jmonitor/var/cache/dev', $bag->cacheDir);
        $this->assertEquals('C:\\Users\\jonat\\www\\jmonitor/var/log', $bag->logDir);
        $this->assertEquals('C:\\Users\\jonat\\www\\jmonitor/var/cache/dev', $bag->buildDir);
        $this->assertEquals('C:\\Users\\jonat\\www\\jmonitor/var/share/dev', $bag->shareDir);

        $this->assertEquals('UTF-8', $bag->charset);

        $this->assertCount(1, $bag->components->scheduler->tasks);
        $this->assertEquals('every 15 seconds', $bag->components->scheduler->tasks[0]->trigger);
        $this->assertEquals('jmonitor:collect', $bag->components->scheduler->tasks[0]->command);
        $this->assertEquals(1767042907, $bag->components->scheduler->tasks[0]->nextRun);
        $this->assertEquals('Collect and send metrics to Jmonitor', $bag->components->scheduler->tasks[0]->description);

        $this->assertTrue($bag->components->flexRecipes->upToDate);
        $this->assertEquals([], $bag->components->flexRecipes->outdatedRecipes);
    }
}
