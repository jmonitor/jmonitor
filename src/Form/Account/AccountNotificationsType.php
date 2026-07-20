<?php

declare(strict_types=1);

namespace App\Form\Account;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountNotificationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subscribedToNewsletter', CheckboxType::class, [
                'label' => 'when there is news or general updates from Jmonitor (newsletter)',
                'required' => false,
            ])
            ->add('subscribedToJmonitorMarketing', CheckboxType::class, [
                'label' => 'for a commercial operation by Jmonitor',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
