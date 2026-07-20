<?php

declare(strict_types=1);

namespace App\Form\Alert\Config;

use App\Alerting\AlertMetric;
use App\Alerting\Config\PercentThresholdConfig;
use App\Entity\Enums\AlertType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @extends AbstractType<PercentThresholdConfig>
 */
class PercentThresholdConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AlertMetric $metric */
        $metric = $options['metric'];

        $builder->add('threshold', PercentType::class, [
            'label' => 'Threshold',
            'type' => 'integer',
            'constraints' => [
                new NotBlank(),
                new Range(min: 1, max: 100),
            ],
            'help' => $metric->getType()->thresholdFormHelp(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PercentThresholdConfig::class,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', ['null', AlertMetric::class]);
    }
}
