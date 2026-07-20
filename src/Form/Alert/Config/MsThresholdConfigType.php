<?php

declare(strict_types=1);

namespace App\Form\Alert\Config;

use App\Alerting\AlertMetric;
use App\Alerting\Config\MsValueThresholdConfig;
use App\Entity\Enums\AlertType;
use App\Form\CustomTypes\MsUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @extends AbstractType<MsValueThresholdConfig>
 */
class MsThresholdConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AlertMetric $metric */
        $metric = $options['metric'];

        $builder->add('threshold', MsUnitType::class, [
            'label' => 'Threshold',
            'constraints' => [
                new NotBlank(),
                new Positive(),
            ],
            'help' => $metric->getType()->thresholdFormHelp(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MsValueThresholdConfig::class,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', ['null', AlertMetric::class]);
    }
}
