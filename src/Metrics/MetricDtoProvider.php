<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Metric\TypicalRangeAwareMetricInterface;
use App\Metrics\Range\TypicalRangesProvider;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\MetricRendererInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;

class MetricDtoProvider implements ResetInterface
{
    private readonly MetricLocator $metricLocator;
    private array $cache = [];
    private readonly ContainerInterface $renderers;
    private TypicalRangesProvider $rangesProvider;

    public function __construct(
        MetricLocator $metricLocator,
        #[AutowireLocator('app.metric.renderer')]
        ContainerInterface $renderers,
        TypicalRangesProvider $rangesProvider,
    ) {
        $this->metricLocator = $metricLocator;
        $this->renderers = $renderers;
        $this->rangesProvider = $rangesProvider;
    }

    public function getDto(Metric $metric, ?Renderer $renderer = null, array $options = []): ?AbstractDto
    {
        $renderer ??= $metric->defaultRenderer();
        $cacheKey = md5(json_encode($options) . $metric->value . $renderer->value);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $service = $this->metricLocator->get($metric);

        if ($service instanceof OptionsAwareMetricInterface) {
            $optionsResolver = new OptionsResolver();
            $service->configureOptions($optionsResolver);
            $options = $optionsResolver->resolve($options);
        }

        /** @var MetricRendererInterface $rendererService */
        $rendererService = $this->renderers->get($renderer->value);

        $dto = $this->cache[$cacheKey] = $rendererService->createDto($service, $options);

        if (!$dto) {
            return null;
        }

        // TODO refactor this range & gauge wiring
        if ($dto instanceof GaugeDto && $dto->value !== null) {
            $ranges = $this->rangesProvider->get($metric);

            if ($ranges) {
                $dto->setBadge($ranges->find($dto->value)?->badge());
            }
        } elseif ($service instanceof TypicalRangeAwareMetricInterface) {
            $ranges = $this->rangesProvider->get($metric);

            if ($ranges) {
                $dto->setBadge($ranges->find($service->getTypicalRangeValue($options))?->badge());
            }
        }

        return $dto;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
