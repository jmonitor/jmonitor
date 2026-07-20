<?php

declare(strict_types=1);

namespace App\Dev;

use Jmonitor\Collector\BootableCollectorInterface;
use Jmonitor\Collector\CollectorInterface;
use Jmonitor\Collector\Php\PhpCollector as JmonitorPhpCollector;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sole surviving dev collector, kept as a TEMPLATE. The other src/Dev fakes were removed
 * because they returned pure rand() data and overwrote the REAL metrics in dev, which
 * defeated self-monitoring.
 *
 * By default this is a transparent pass-through: collect() returns the real PHP collector
 * output unchanged. The #[When('dev')] decorator pattern of this namespace exists so you can
 * augment or force values for a metric/section that has no real source in the dev container
 * — e.g. PHP-FPM, which does not exist under FrankenPHP worker mode. Uncomment and adapt the
 * `fpm` block below to populate the FPM dashboard section (or to force any value while
 * testing). SumTrait::sum() turns a per-tick delta into a monotonic cumulative counter.
 */
#[When('dev')]
#[AsDecorator(decorates: JmonitorPhpCollector::class, onInvalid: ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]
class PhpCollector implements CollectorInterface, BootableCollectorInterface
{
    use SumTrait;

    private JmonitorPhpCollector $decorated;

    public function __construct(JmonitorPhpCollector $decorated)
    {
        $this->decorated = $decorated;
    }

    public function boot(): void
    {
        $this->decorated->boot();
    }

    public function collect(): array
    {
        $metrics = $this->decorated->collect();

        // Example — force a synthetic FPM section (no PHP-FPM under FrankenPHP worker mode).
        // Uncomment to populate the FPM dashboard section in dev:
        //
        // $metrics['fpm'] = [
        //     'pool' => 'www',
        //     'process-manager' => 'dynamic',
        //     'start-since' => 3600,
        //     'accepted-conn' => $this->sum('fpm_accepted_conn', rand(0, 100)),
        //     'idle-processes' => rand(1, 10),
        //     'active-processes' => rand(1, 5),
        //     'max-active-processes' => rand(5, 20),
        //     'max-children-reached' => rand(0, 1),
        //     'slow-requests' => $this->sum('fpm_slow_requests', rand(0, 1) > 0.9 ? 1 : 0),
        //     'memory-peak' => rand(1000000, 50000000), // 1MB to 50MB
        // ];

        return $metrics;
    }

    public function getVersion(): int
    {
        return $this->decorated->getVersion();
    }

    public function getName(): string
    {
        return $this->decorated->getName();
    }
}
