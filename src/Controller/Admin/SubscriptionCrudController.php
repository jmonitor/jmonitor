<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Field\CreatedAtField;
use App\Entity\Enums\Plan;
use App\Entity\Subscription;
use App\Plan\Edition;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Edition $edition,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        // configureCrud() is invoked by EasyAdmin for every CRUD action:
        // a single guard covers index/detail/edit/new, including direct URL access.
        if ($this->edition->isSelfHosted()) {
            throw new NotFoundHttpException('Billing is not available in the self-hosted edition.');
        }

        return $crud;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $displayIfSafe = fn(Action $action) => $action->displayIf($this->isSafeToDelete(...));

        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, $displayIfSafe)
            ->update(Crud::PAGE_DETAIL, Action::DELETE, $displayIfSafe)
        ;
    }

    /**
     * Deleting a subscription is only safe when the row mirrors nothing: no Stripe
     * subscription to drift from, and no bucket left for the purge to tear down.
     * Every other case belongs to Stripe (cancel there, the webhook does the rest)
     * or to PurgeExpiredSubscriptionsCommand once the subscription expires.
     */
    private function isSafeToDelete(Subscription $subscription): bool
    {
        return !$subscription->getStripeSubscriptionId()
            && !$subscription->getProject()?->getBucketId();
    }

    /**
     * Server-side counterpart of the isSafeToDelete() guard above: the delete button is
     * already hidden for the unsafe cases, so reaching this means the UI was bypassed.
     *
     * Project is the owning side of the relation, so nothing updates project.subscription_id
     * when the subscription alone is removed. Clearing it first is what
     * PurgeExpiredSubscriptionsCommand does, and drops the project back to the free plan.
     */
    #[\Override]
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof Subscription) {
            if (!$this->isSafeToDelete($entityInstance)) {
                throw new \LogicException('This subscription cannot be deleted: cancel it in Stripe, or let PurgeExpiredSubscriptionsCommand tear it down once expired.');
            }

            $entityInstance->setProject(null);
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            CreatedAtField::new(),
            AssociationField::new('project', 'Project'),
            // Restrict to purchasable plans: Plan::SELF_HOSTED must never be persisted on a Subscription
            // (it's produced only by the self-hosted edition's plan resolver, never stored).
            ChoiceField::new('plan', 'Plan')->setChoices(Plan::orderedCases()),
            DateField::new('endAt', 'End date'),
            TextField::new('stripeSubscriptionId', 'Stripe Subscription ID')->hideOnIndex()->setDisabled(),
            BooleanField::new('autoRenew', 'Auto renew'),
        ];
    }
}
