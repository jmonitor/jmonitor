<?php

declare(strict_types=1);

namespace App\Form\Security;

use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PasswordLostType extends AbstractType
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'register.email.label',
                'attr' => ['autocomplete' => 'email', 'autofocus' => true],
                'constraints' => [
                    new NotBlank(),
                    new Callback($this->validate(...)),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function validate(mixed $value, ExecutionContextInterface $context, mixed $payload): void
    {
        $user = $this->userRepository->findOneBy(['email' => $value]);

        if (!$user) {
            $context->buildViolation('No user found with this e-mail.')
                ->atPath('email')
                ->addViolation();
        }
    }
}
