<?php

declare(strict_types=1);

namespace App\Form;

use App\AutoRefresh\Dto\AutoRefreshDto;
use App\Security\Voter\Right\Right;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<AutoRefreshDto> */
class AutoRefreshType extends AbstractType
{
    public const string NAME = 'auto_refresh';

    private readonly Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoRefresh', CheckboxType::class, [
                'label' => 'Live',
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => !$this->security->isGranted(Right::AUTOREFRESH->value),
                ],
            ])
            // a form with only an unchecked checkbox sends an empty payload, hence this extra field
            ->add('autorefreshForm', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => 1,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutoRefreshDto::class,
            'csrf_protection' => false,
        ]);
    }
}
