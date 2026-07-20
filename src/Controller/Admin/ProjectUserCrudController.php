<?php

namespace App\Controller\Admin;

use App\Admin\Field\CreatedAtField;
use App\Entity\Enums\ProjectRole;
use App\Entity\ProjectUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/**
 * @extends AbstractCrudController<ProjectUser>
 */
class ProjectUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectUser::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            CreatedAtField::new(),
            AssociationField::new('user', 'User'),
            AssociationField::new('project', 'Project'),
            ChoiceField::new('role', 'Role')->setChoices(ProjectRole::cases()),
            BooleanField::new('alertNotificationsEnabled')->hideOnIndex(),
        ];
    }
}
