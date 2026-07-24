<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Options\DefaultRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Twig\Environment;

#[AsTaggedItem(Renderer::Basic->value)]
readonly class BasicRenderer implements MetricRendererInterface
{
    public function __construct(
        private Environment $twig,
        private MetricsBagProvider $bagProvider,
    ) {}

    /**
     * @param BasicDto $dto
     */
    public function render($dto, RendererOptionsInterface $options): string
    {
        $metricTemplate = 'dash/project/metrics/basic/custom/' . $dto->metric->value . '.html.twig';

        $template = $this->twig->getLoader()->exists($metricTemplate) ? $metricTemplate : 'dash/project/metrics/basic/formatted_value.html.twig';

        return $this->twig->render($template, [
            'dto' => $dto,
            'options' => $options,
        ]);
    }

    /**
     * @param BasicMetricInterface $metric
     */
    public function createDto(MetricInterface $metric, array $dtoOptions): ?BasicDto
    {
        $dto = new BasicDto($metric->getMetric());

        $metric->configureBasicDto($dto, $dtoOptions);

        // Custom templates read their component bag directly via bag(); mark the
        // card unavailable when no bag was collected so the no-data view renders
        // instead of the template dereferencing a null bag.
        if (!$this->bagProvider->getComponentBags($metric->getMetric()->component())) {
            $dto->setValueAvailable(false);
        }

        return $dto;
    }

    public function createRendererOptions(): DefaultRendererOptions
    {
        return new DefaultRendererOptions();
    }
}
