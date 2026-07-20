<?php

declare(strict_types=1);

namespace App\Form\Alert;

use App\Alerting\AlertMetric;
use App\Entity\Alert;
use App\Entity\Enums\Component;
use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @extends AbstractType<Alert>
 */
class AlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        /** @var Alert $alert */
        $alert = $builder->getData();

        /** @var Component $component */
        $component = $options['component'];

        /** @var Project $project */
        $project = $alert->getProject();

        $builder
            ->add('alertMetric', ChoiceType::class, [
                'label' => 'Metric',
                'constraints' => [
                    new NotBlank(),
                ],
                'placeholder' => 'Select...',
                'choices' => array_filter(iterator_to_array($component->alertMetrics()), function (AlertMetric $alertMetric) use ($component, $project, $alert): bool {
                    // when editing, keep only the metric being edited
                    if ($alert->getId() !== null) {
                        return $alert->getAlertMetric() === $alertMetric;
                    }

                    // otherwise, don't offer the metrics that already have an alert configured
                    return array_all(
                        $project->getAlertsByComponent($component),
                        fn(Alert $alert): bool => $alert->getAlertMetric() !== $alertMetric,
                    );
                }),
                'choice_label' => static fn(AlertMetric $alertMetric): string => $alertMetric->label(),
                'disabled' => $alert->getId() !== null,
            ])
        ;

        $builder->addDependent('config', 'alertMetric', static function (DependentField $field, ?AlertMetric $metric): void {
            if (!$metric) {
                return;
            }

            $formType = $metric->configFormTypeClass();

            if (!$formType) {
                return;
            }

            $field->add($formType, [
                'label' => false,
                'by_reference' => false,
                'metric' => $metric,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Alert::class,
            'component' => null,
            'attr' => [
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'save',
            ],
        ]);
    }
}
