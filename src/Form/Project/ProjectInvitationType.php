<?php

declare(strict_types=1);

namespace App\Form\Project;

use App\Entity\Enums\ProjectRole;
use App\Entity\ProjectInvitation;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProjectInvitationType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ProjectContext $projectContext, private readonly Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Role',
                'choices' => $this->getRoleChoices(),
                'choice_label' => fn(ProjectRole $role): string => $role->value,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectInvitation::class,
            'constraints' => [
                new UniqueEntity(fields: ['email', 'project'], message: 'This email is already invited to this project'),
                new Callback($this->notAlreadyUser(...)),
            ],
        ]);
    }

    public function notAlreadyUser(ProjectInvitation $invitation, ExecutionContextInterface $context, mixed $payload): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $invitation->getEmail(),
        ]);

        if (!$user) {
            return;
        }

        $exist = $this->em->getRepository(ProjectUser::class)->findOneBy([
            'user' => $user,
            'project' => $this->projectContext->getCurrentProject(),
        ]);

        if (!$exist) {
            return;
        }

        $context->buildViolation('This email is already used in this project')
            ->atPath('email')
            ->addViolation();
    }

    /**
     * @return iterable<ProjectRole>
     */
    private function getRoleChoices(): iterable
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $project = $this->projectContext->getCurrentProject();
        $currentRole = $user->getRoleInProject($project);

        foreach (ProjectRole::cases() as $role) {
            if ($currentRole->isHigherThan($role) || $currentRole->isOwner()) {
                yield $role;
            }
        }
    }
}
