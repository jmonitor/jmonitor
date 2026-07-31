<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A range slider whose "unset" state is the resolved default: a null model value shows the
 * default (a valueless range input would park the handle at the midpoint), and submitting
 * the default stores null again, so the option keeps following future default changes.
 */
class SliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default = (float) $options['default'];
        $tolerance = ((float) $options['step']) / 2;

        $builder->addModelTransformer(new CallbackTransformer(
            static fn(?float $value): float => $value ?? $default,
            static function (mixed $value) use ($default, $tolerance): ?float {
                if ($value === null || $value === '') {
                    return null;
                }

                if (!is_numeric($value)) {
                    throw new TransformationFailedException('This value should be a valid number.');
                }

                // Round off floating-point representation noise (e.g. abs(2.75 - 2.8) can land
                // a hair under 0.05 instead of exactly on it) so the boundary comparison below
                // reflects the real decimal values rather than binary rounding artefacts.
                $diff = round(abs((float) $value - $default), 9);

                return $diff < $tolerance ? null : (float) $value;
            },
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['default'] = (float) $options['default'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'min' => 0.0,
            'max' => 1.0,
            'step' => 0.1,
            'required' => false,
        ]);

        $resolver->setRequired('default');
        $resolver->setAllowedTypes('default', ['float', 'int']);
        $resolver->setAllowedTypes('min', ['float', 'int']);
        $resolver->setAllowedTypes('max', ['float', 'int']);
        $resolver->setAllowedTypes('step', ['float', 'int']);

        $resolver->setNormalizer('attr', static fn(Options $options, array $attr): array => $attr + [
            'min' => (float) $options['min'],
            'max' => (float) $options['max'],
            'step' => (float) $options['step'],
            'data-action' => 'input->option-slider#update',
        ]);
    }

    public function getParent(): string
    {
        return RangeType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'slider';
    }
}
