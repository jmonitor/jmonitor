<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;
use App\Form\Type\SliderType;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * The options object is readonly, so the form rebuilds it instead of writing into it.
 */
class TimeSeriesEmbedOptionsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var TimeSeriesChartConfiguration $defaults */
        $defaults = $options['defaults'];

        $builder->add('range', EnumType::class, [
            'label' => 'Range',
            'class' => TimeRange::class,
            'choice_label' => static fn(TimeRange $range): string => $range->label(),
            'required' => false,
        ]);

        $builder->add('aspectRatio', SliderType::class, [
            'label' => 'Aspect ratio',
            'default' => $defaults->aspectRatio ?? 2.8,
            'min' => TimeSeriesEmbedOptions::ASPECT_RATIO_MIN,
            'max' => TimeSeriesEmbedOptions::ASPECT_RATIO_MAX,
            'step' => TimeSeriesEmbedOptions::ASPECT_RATIO_STEP,
            'constraints' => new Range(
                min: TimeSeriesEmbedOptions::ASPECT_RATIO_MIN,
                max: TimeSeriesEmbedOptions::ASPECT_RATIO_MAX,
            ),
        ]);

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeSeriesEmbedOptions::class,
            'empty_data' => static fn(): TimeSeriesEmbedOptions => new TimeSeriesEmbedOptions(),
        ]);

        $resolver->setRequired('defaults');
        $resolver->setAllowedTypes('defaults', TimeSeriesChartConfiguration::class);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);

        $forms['range']->setData($viewData?->range);
        $forms['aspectRatio']->setData($viewData?->aspectRatio);
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $forms = iterator_to_array($forms);

        $viewData = new TimeSeriesEmbedOptions(
            range: $forms['range']->getData(),
            aspectRatio: $forms['aspectRatio']->getData(),
        );
    }
}
