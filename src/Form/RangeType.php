<?php

declare(strict_types=1);

namespace App\Form;

use App\Chart\TimeRange;
use App\Range\Dto\RangeDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('range', EnumType::class, [
                'label' => false,
                'class' => TimeRange::class,
                'constraints' => [
                    new NotBlank(),
                ],
                'choice_label' => static fn(TimeRange $range): string => $range->label(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RangeDto::class,
            'csrf_protection' => false,
        ]);
    }
}
