<?php

declare(strict_types=1);

namespace App\Form\Account;

use App\Entity\ProjectUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ProjectUser>
 */
class ProjectUserAlertNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event): void {
            /** @var ProjectUser $data */
            $data = $event->getData();

            $event->getForm()->add('alertNotificationsEnabled', CheckboxType::class, [
                'label' => $data->getProject()->getName(),
                'required' => false,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectUser::class,
        ]);
    }
}
