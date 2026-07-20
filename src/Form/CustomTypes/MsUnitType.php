<?php

declare(strict_types=1);

namespace App\Form\CustomTypes;

use App\Form\DataTransformer\MsUnitTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MsUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', NumberType::class, [
                'label' => false,
                'required' => $options['required'],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'ms' => 'ms',
                    's' => 's',
                ],
            ]);

        $builder->addModelTransformer(new MsUnitTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'error_bubbling' => false,
        ]);
    }
}
