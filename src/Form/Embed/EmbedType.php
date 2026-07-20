<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Chart\TimeRange;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Project\ProjectContext;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @extends AbstractType<EmbedDto>
 */
class EmbedType extends AbstractType
{
    private readonly ProjectContext $projectContext;

    public function __construct(ProjectContext $projectContext)
    {
        $this->projectContext = $projectContext;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add('metric', EnumType::class, [
            'label' => 'Metric',
            'class' => Metric::class,
            'choice_label' => fn(Metric $metric): string => $metric->getLabel() ?? $metric->value,
            'choice_filter' => fn(Metric $metric): bool => $this->projectContext->getCurrentProject()->hasComponent($metric->component()),
            'group_by' => fn(Metric $metric): string => $metric->component()->label(),
            'constraints' => new NotBlank(),
        ]);

        $builder->addDependent('renderer', 'metric', function (DependentField $field, ?Metric $metric): void {
            if (!$metric) {
                return;
            }

            $availableRenderers = $metric->availableRenderers();
            $field->add(ChoiceType::class, [
                'label' => 'Style',
                'choices' => $availableRenderers,
                'choice_label' => fn(Renderer $renderer): string => $renderer->styleLabel(),
            ]);
        });

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

        $builder->add('autoRefresh', CheckboxType::class, [
            'label' => 'AutoRefresh',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'attr' => [
                'onchange' => 'this.requestSubmit()',
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
