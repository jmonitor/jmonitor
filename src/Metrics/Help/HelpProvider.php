<?php

declare(strict_types=1);

namespace App\Metrics\Help;

use App\Metrics\Help\Dto\Help;
use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;
use Twig\Loader\LoaderInterface;

class HelpProvider
{
    /** @var array<string, Help> */
    private array $instances = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire(service: 'twig.loader.native_filesystem')]
        private readonly LoaderInterface $templates,
    ) {
        $config = require $projectDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'metric_help.php';

        $this->load($config);
    }

    #[AsTwigFunction('metric_help')]
    public function getHelp(Metric|string $metric): ?Help
    {
        return $this->instances[$metric instanceof Metric ? $metric->value : $metric] ?? null;
    }

    /**
     * A metric is documented by a config entry, by a dedicated template, or both.
     */
    #[AsTwigFunction('metric_has_help')]
    public function hasHelp(Metric $metric): bool
    {
        return $this->getHelp($metric) !== null || $this->templates->exists(self::template($metric));
    }

    public static function template(Metric $metric): string
    {
        return 'dash/help/metric/' . $metric->value . '.html.twig';
    }

    /**
     * @param array<string, array{definitions?: string[], why_it_matters?: string[], how_to_read?: string[], actions?: mixed[], good_to_know?: string[]}> $config
     */
    private function load(array $config): void
    {
        foreach ($config as $name => $conf) {
            $this->instances[$name] = new Help(
                $conf['definitions'] ?? [],
                $conf['why_it_matters'] ?? [],
                $conf['how_to_read'] ?? [],
                $conf['actions'] ?? [],
                $conf['good_to_know'] ?? [],
            );
        }
    }
}
