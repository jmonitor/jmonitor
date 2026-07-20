<?php

declare(strict_types=1);

namespace App\Form\CustomTypes;

use App\Form\DataTransformer\ByteUnitTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ByteUnitType extends AbstractType
{
    private const array DECIMAL_UNITS = ['B' => 'B', 'kB' => 'kB', 'MB' => 'MB', 'GB' => 'GB', 'TB' => 'TB'];
    private const array BINARY_UNITS = ['B' => 'B', 'KiB' => 'KiB', 'MiB' => 'MiB', 'GiB' => 'GiB', 'TiB' => 'TiB'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', NumberType::class, [
                'label' => false,
                'required' => $options['required'],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => false,
                'choices' => $this->getUnitChoices($options['use_binary'], $options['isPerSecond']),
            ]);

        $builder->addModelTransformer(new ByteUnitTransformer($options['use_binary']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'error_bubbling' => false,
            'use_binary' => true,
            'isPerSecond' => false,
        ]);

        $resolver->setAllowedTypes('use_binary', 'bool');
        $resolver->setAllowedTypes('isPerSecond', 'bool');
    }

    private function getUnitChoices(bool $useBinary, bool $isPerSecond): array
    {
        $choices = $useBinary ? self::BINARY_UNITS : self::DECIMAL_UNITS;

        if ($isPerSecond) {
            $choices = array_map(function ($unit) {
                return $unit . '/s';
            }, $choices);

            $choices = array_combine(array_values($choices), array_keys($choices));
        }

        return $choices;
    }
}
