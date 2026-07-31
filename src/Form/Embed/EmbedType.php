<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Metrics\Renderer\ChartDefaultsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * Options for the embed sidebar. The metric is fixed by the entry point (metric card
 * menu or an existing embed) and passed as the "metric" form option.
 *
 * Which options exist is answered once, by EmbedOptionsFactory: the form never asks
 * a renderer whether it supports a range.
 */
class EmbedType extends AbstractType
{
    public function __construct(
        private readonly ChartDefaultsResolver $defaultsResolver,
    ) {}

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

            // The dependent callback fires at most twice per form instance: once on the
            // initial render (PRE_SET_DATA, where the renderer and chart data passed in
            // always match) and once more on every submission (POST_SUBMIT), regardless of
            // whether the renderer actually changed. On that POST_SUBMIT run, the chart
            // subform is rebuilt fresh and Symfony's parent-to-child mapping would otherwise
            // re-apply the *pre-submission* parent data to it — data matching the old
            // renderer, which may now be the wrong type for the new subform and crash. It is
            // safe to blank that stale value on every submit (not just on an actual switch):
            // the subform still receives its real submitted values straight from the request
            // via its own submit() call right after, independently of this reset.
            $initialBuild = true;

            $builder->addDependent('chart', 'renderer', function (DependentField $field, ?Renderer $renderer) use ($metric, &$initialBuild): void {
                if (!$renderer) {
                    return;
                }

                $this->addChartOptions($field, $metric, $renderer, resetData: !$initialBuild);
                $initialBuild = false;
            });
        } else {
            $this->addChartOptions($builder, $metric, $renderers[0]);
        }

        $builder->add('card', CardEmbedOptionsType::class, ['label' => false]);

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

    private function addChartOptions(DependentField|FormBuilderInterface $target, Metric $metric, Renderer $renderer, bool $resetData = false): void
    {
        $formType = EmbedOptionsFactory::formType($renderer);

        if (!$formType) {
            return;
        }

        $options = [
            'label' => false,
            'defaults' => $this->defaultsResolver->resolve($metric, $renderer),
        ];

        if ($resetData) {
            $options['data'] = null;
        }

        if ($target instanceof DependentField) {
            $target->add($formType, $options);

            return;
        }

        $target->add('chart', $formType, $options);
    }
}
