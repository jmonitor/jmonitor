<?php

namespace App\Controller\Admin;

use App\Admin\Field\CreatedAtField;
use App\Entity\Enums\Component;
use App\Entity\Enums\ProjectStatus;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\PlanResolver;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Project>
 */
class ProjectCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PlanResolver $planResolver,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'name'])
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('uuid', 'UUID')->onlyOnDetail(),
            CreatedAtField::new(),
            TextField::new('Name', 'Name'),
            AssociationField::new('subscription', 'Subscription')->formatValue(fn(?Subscription $subscription, Project $project) => $this->planResolver->resolve($project)->label())->renderAsEmbeddedForm(),
            ChoiceField::new('status', 'Status')->setChoices(ProjectStatus::cases())->renderAsBadges(),
            TextField::new('apiKey', 'Api key')->onlyOnDetail(),
            TextField::new('bucketId')->onlyOnDetail(),
            TextField::new('bucketName')->onlyOnDetail(),
            ChoiceField::new('components', 'Components')->onlyOnDetail()->setChoices(Component::cases()),
        ];
    }
}
