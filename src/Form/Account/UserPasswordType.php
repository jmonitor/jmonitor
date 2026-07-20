<?php

declare(strict_types=1);

namespace App\Form\Account;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserPasswordType extends AbstractType
{
    public function __construct(private readonly Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['need_current_password'] && $this->getUser()->getPassword()) {
            $builder->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => new UserPassword(),
                'required' => false,
            ]);
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => new NotBlank(),
                    'label' => 'New password',
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'New password (again)',
                ],
                'invalid_message' => 'The two passwords do not match.',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'need_current_password' => false,
        ]);
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
