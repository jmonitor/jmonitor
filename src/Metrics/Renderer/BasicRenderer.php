<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Options\DefaultRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Twig\Environment;

// TODO could add a renderUnavailable or renderEmpty function..
#[AsTaggedItem(Renderer::Basic->value)]
readonly class BasicRenderer implements MetricRendererInterface
{
    public function __construct(
        private Environment $twig,
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

        return $dto;
    }

    public function createRendererOptions(): DefaultRendererOptions
    {
        return new DefaultRendererOptions();
    }
}
