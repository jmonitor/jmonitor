<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Chart\Dto\GaugeChartConfiguration;
use App\Form\Type\SliderType;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * The options object is readonly, so the form rebuilds it instead of writing into it.
 */
class GaugeEmbedOptionsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var GaugeChartConfiguration $defaults */
        $defaults = $options['defaults'];

        $builder->add('aspectRatio', SliderType::class, [
            'label' => 'Aspect ratio',
            'default' => $defaults->aspectRatio,
            'min' => GaugeEmbedOptions::ASPECT_RATIO_MIN,
            'max' => GaugeEmbedOptions::ASPECT_RATIO_MAX,
            'step' => GaugeEmbedOptions::ASPECT_RATIO_STEP,
            'constraints' => new Range(
                min: GaugeEmbedOptions::ASPECT_RATIO_MIN,
                max: GaugeEmbedOptions::ASPECT_RATIO_MAX,
            ),
        ]);

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GaugeEmbedOptions::class,
            'empty_data' => static fn(): GaugeEmbedOptions => new GaugeEmbedOptions(),
        ]);

        $resolver->setRequired('defaults');
        $resolver->setAllowedTypes('defaults', GaugeChartConfiguration::class);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);

        $forms['aspectRatio']->setData($viewData?->aspectRatio);
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $forms = iterator_to_array($forms);

        $viewData = new GaugeEmbedOptions(aspectRatio: $forms['aspectRatio']->getData());
    }
}
