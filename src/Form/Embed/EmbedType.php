<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Chart\TimeRange;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * Options for the embed sidebar. The metric is fixed by the entry point (metric card
 * menu or an existing embed) and passed as the "metric" form option.
 */
class EmbedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Metric $metric */
        $metric = $options['metric'];
        $renderers = $metric->availableRenderers();

        if (count($renderers) >= 2) {
            $builder = new DynamicFormBuilder($builder);

            $builder->add('renderer', ChoiceType::class, [
                'label' => 'Style',
                'choices' => $renderers,
                'choice_label' => fn(Renderer $renderer): string => $renderer->styleLabel(),
                'choice_value' => static fn(?Renderer $renderer): ?string => $renderer?->value,
            ]);

            $builder->addDependent('range', 'renderer', function (DependentField $field, ?Renderer $renderer): void {
                if (!$renderer || !$renderer->supportRange()) {
                    return;
                }

                $field->add(EnumType::class, [
                    'label' => 'Range',
                    'class' => TimeRange::class,
                    'choice_label' => static fn(TimeRange $range): string => $range->label(),
                    'required' => false,
                ]);
            });

            $builder->addDependent('chartConfig', 'renderer', function (DependentField $field, ?Renderer $renderer): void {
                if (!$renderer || !$renderer->supportRange()) {
                    return;
                }

                $field->add(ChartConfigType::class, [
                    'label' => false,
                    'renderer' => $renderer,
                ]);
            });
        } elseif ($renderers[0]->supportRange()) {
            $builder->add('range', EnumType::class, [
                'label' => 'Range',
                'class' => TimeRange::class,
                'choice_label' => static fn(TimeRange $range): string => $range->label(),
                'required' => false,
            ]);

            $builder->add('chartConfig', ChartConfigType::class, [
                'label' => false,
                'renderer' => $renderers[0],
            ]);
        }

        $builder->add('showProjectName', CheckboxType::class, [
            'label' => 'Show project name',
            'required' => false,
        ]);

        // Driven by the preview's Live toggle (embed-form Stimulus controller), '1' or ''.
        $builder->add('autoRefresh', HiddenType::class, [
            'attr' => ['data-embed-form-target' => 'autoRefreshInput'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'attr' => [
                'onchange' => 'this.requestSubmit()',
                'data-embed-form-target' => 'form',
            ],
            'allow_extra_fields' => true,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', Metric::class);
    }
}
