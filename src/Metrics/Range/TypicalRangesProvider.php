<?php

declare(strict_types=1);

namespace App\Metrics\Range;

use App\Metrics\Metric;
use App\Metrics\Range\Dto\Range;
use App\Metrics\Range\Dto\Ranges;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

class TypicalRangesProvider
{
    /** @var array<string, Ranges> */
    private array $instances = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $config = require $projectDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'typical_metric_ranges.php';

        // everything can be loaded upfront; it stays in memory in worker mode
        $this->load($config);
    }

    #[AsTwigFunction('metric_ranges')]
    public function get(Metric|string $metric): ?Ranges
    {
        return $this->instances[$metric instanceof Metric ? $metric->value : $metric] ?? null;
    }

    private function load(array $config): void
    {
        foreach ($config as $name => $conf) {
            $ranges = [];
            foreach ($conf['ranges'] as $range) {
                $ranges[] = new Range($range[0], $range[1], $range[2], $range[3], $range[4], $range[5] ?? null);
            }

            $this->instances[$name] = new Ranges($ranges, $conf['note']);
        }
    }
}
