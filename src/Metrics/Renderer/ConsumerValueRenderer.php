<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Metric\ConsumerValueMetricInterface;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\ConsumerValueDto;
use App\Metrics\Renderer\Options\DefaultRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Twig\Environment;

#[AsTaggedItem(Renderer::ConsumerValue->value)]
readonly class ConsumerValueRenderer implements MetricRendererInterface
{
    public function __construct(
        private MetricsBagProvider $metricsCacheManager,
        private Environment $twig,
    ) {}

    /**
     * @param ConsumerValueDto $dto
     */
    public function render($dto, RendererOptionsInterface $options): string
    {
        $metricTemplate = 'dash/project/metrics/consumer_value/metric/' . $dto->metric->value . '.html.twig';

        $template = $this->twig->getLoader()->exists($metricTemplate) ? $metricTemplate : 'dash/project/metrics/consumer_value/value.html.twig';

        return $this->twig->render($template, [
            'value' => $dto->formatter ? ($dto->formatter)($dto->value) : $dto->value,
            'options' => $options,
            'dto' => $dto,
        ]);
    }

    /**
     * @param ConsumerValueMetricInterface $metric
     */
    public function createDto(MetricInterface $metric, array $dtoOptions): ?ConsumerValueDto
    {
        $bag = $this->metricsCacheManager->getLastBag($metric->getConsumer());

        if (!$bag) {
            return null;
        }

        $dto = new ConsumerValueDto($metric->getMetric(), $metric->getConsumer(), $bag, $metric->getValue($bag));

        $metric->configureValueDto($dto);

        return $dto;
    }

    public function createRendererOptions(): DefaultRendererOptions
    {
        return new DefaultRendererOptions();
    }
}
