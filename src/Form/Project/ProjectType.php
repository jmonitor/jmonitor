<?php

declare(strict_types=1);

namespace App\Form\Project;

use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Form\CustomTypes\ProjectNameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', ProjectNameType::class)
            ->add('components', ChoiceType::class, [
                'label' => 'My stack components',
                'help' => 'Select the components you want to monitor',
                'choices' => Component::menuOrderedCases(),
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
