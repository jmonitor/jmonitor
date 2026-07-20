<?php

declare(strict_types=1);

namespace App\Form\Alert\Config;

use App\Alerting\AlertMetric;
use App\Alerting\Config\OutdatedVersion;
use App\Alerting\Config\OutdatedVersionConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<OutdatedVersionConfig>
 */
class OutdatedVersionConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('threshold', EnumType::class, [
            'class' => OutdatedVersion::class,
            'label' => 'Threshold',
            'constraints' => new NotBlank(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OutdatedVersionConfig::class,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', ['null', AlertMetric::class]);
    }
}
