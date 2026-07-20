<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Field\CreatedAtField;
use App\Entity\Enums\Role;
use App\Entity\Enums\UserStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setSearchFields(['id', 'email'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setFormOptions([
                'constraints' => new UniqueEntity(fields: 'email'),
            ])
        ;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $impersonateAction = Action::new('impersonate', 'Impersonate', 'fa-solid fa-repeat')
            ->setHtmlAttributes(['target' => '_blank'])
            ->linkToUrl(fn(User $user): string => $this->urlGenerator->generate('dashboard', [
                '_switch_user' => $user->getEmail(),
            ]));

        return $actions->add(Crud::PAGE_DETAIL, $impersonateAction);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('uuid', 'UUID')->onlyOnDetail(),
            CreatedAtField::new(),
            TextField::new('ip', 'Signup IP')->onlyOnDetail(),
            EmailField::new('email', 'E-mail'),
            ChoiceField::new('role', 'Role')->setChoices(Role::cases()),
            ChoiceField::new('status', 'Status')->setChoices(UserStatus::cases()),
            DateField::new('lastConnectedDate', 'Last visit')->onlyOnDetail()->setFormat('long'),
            TextField::new('plainPassword', 'Password (change)')
                ->setFormType(TextType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'attr' => ['autocomplete' => 'new-password'],
                ])
                ->onlyOnForms(),
        ];
    }

    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->hashPasswordIfNeeded($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    #[\Override]
    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->hashPasswordIfNeeded($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPasswordIfNeeded(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plain = $entityInstance->getPlainPassword();
        if (!$plain) {
            return;
        }

        $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plain));
        $entityInstance->setPlainPassword(null);
    }
}
