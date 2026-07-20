<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Metrics\Renderer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class ChartConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Renderer $renderer */
        $renderer = $options['renderer'];

        switch ($renderer) {
            case Renderer::Line:
            case Renderer::Bar:
                $this->buildFormForTimeSeries($builder, $options);
                break;
            case Renderer::Gauge:
                $this->buildFormForGauge($builder, $options);
                break;
            default:
                throw new \LogicException(sprintf('No chart config form is defined for the "%s" renderer.', $renderer->value));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);

        $resolver->setRequired('renderer');
    }

    private function buildFormForTimeSeries(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('aspectRatio', NumberType::class, [
            'label' => 'Aspect Ratio',
            'scale' => 1,
            'required' => false,
            'constraints' => new Range(
                min: 0.1,
                max: 5,
            ),
        ]);
    }

    private function buildFormForGauge(FormBuilderInterface $builder, array $options): void {}
}
