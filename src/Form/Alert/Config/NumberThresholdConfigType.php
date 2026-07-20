<?php

declare(strict_types=1);

namespace App\Form\Alert\Config;

use App\Alerting\AlertMetric;
use App\Alerting\Config\NumberThresholdConfig;
use App\Alerting\Unit;
use App\Form\CustomTypes\ByteUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @extends AbstractType<NumberThresholdConfig>
 */
class NumberThresholdConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AlertMetric $alertMetric */
        $alertMetric = $options['metric'];

        switch (true) {
            case $alertMetric->unit() === Unit::Byte:
            case $alertMetric->unit() === Unit::BytePerSec:
                $builder->add('threshold', ByteUnitType::class, [
                    'label' => 'Threshold',
                    'constraints' => [
                        new NotBlank(),
                        new PositiveOrZero(),
                    ],
                    'isPerSecond' => $alertMetric->unit() === Unit::BytePerSec,
                    'help' => $alertMetric->getType()->thresholdFormHelp(),
                ]);
                break;
            default:
                $builder->add('threshold', NumberType::class, [
                    'label' => 'Threshold',
                    'constraints' => [
                        new NotBlank(),
                        new PositiveOrZero(),
                    ],
                    'help' => $alertMetric->getType()->thresholdFormHelp(),
                ]);
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NumberThresholdConfig::class,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', ['null', AlertMetric::class]);
    }
}
