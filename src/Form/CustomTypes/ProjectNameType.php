<?php

declare(strict_types=1);

namespace App\Form\CustomTypes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ProjectNameType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Name',
            'constraints' => [
                new NotBlank(),
                new Regex('/^[a-zA-Z0-9_\- ]+$/'),
                new Length(max: 32),
            ],
            'help' => 'Only letters, numbers, dashes, underscores and spaces are allowed.',
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }
}
