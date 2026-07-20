<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\Subscription;
use App\Entity\User;
use App\Plan\Edition;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        #[Autowire(env: 'APP_DOMAIN')]
        private readonly string $domain,
        private readonly Edition $edition,
    ) {}

    #[\Override]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[\Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Jmonitor');
    }

    #[\Override]
    public function configureActions(): Actions
    {
        $actions = parent::configureActions();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    #[\Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-user');
        yield MenuItem::linkTo(ProjectCrudController::class, 'Projects', 'fa fa-folder');
        yield MenuItem::linkTo(ProjectUserCrudController::class, 'UserProjects', 'fas fa-building-user');
        if ($this->edition->isCloud()) {
            yield MenuItem::linkTo(SubscriptionCrudController::class, 'Subscriptions', 'fa fa-dollar-sign');
        }
        yield MenuItem::section('<hr>');
        yield MenuItem::linkToUrl('Back to dash', 'fa-solid fa-arrow-up-right-from-square', '//dash.' . $this->domain);
    }
}
