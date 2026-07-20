<?php

declare(strict_types=1);

namespace App\Form\Alert\Config;

use App\Alerting\AlertMetric;
use App\Alerting\Config\SymfonyTransportMessagesConfig;
use App\Entity\Enums\AlertType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @extends AbstractType<SymfonyTransportMessagesConfig>
 */
class SymfonyTransportMessagesConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('transportName', TextType::class, [
            'label' => 'Transport name',
            'constraints' => new NotBlank(),
        ]);

        $builder->add('threshold', NumberType::class, [
            'label' => 'Threshold',
            'constraints' => [
                new NotBlank(),
                new PositiveOrZero(),
            ],
            'help' => AlertType::MaxValueThreshold->thresholdFormHelp(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SymfonyTransportMessagesConfig::class,
        ]);

        $resolver->setRequired('metric');
        $resolver->setAllowedTypes('metric', ['null', AlertMetric::class]);
    }
}
