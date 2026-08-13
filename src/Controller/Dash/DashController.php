<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\User;
use App\Project\ProjectContext;
use App\Version\UpdateChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DashController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dash(#[CurrentUser] User $user, ProjectContext $projectContext): Response
    {
        if (count($user->getProjectUsers()) === 0) {
            return $this->redirectToRoute('project.create');
        }

        $project = $projectContext->getCurrentProject();

        if ($project) {
            return $this->redirectToRoute('project.dashboard', ['uuid' => $project->getUuid()]);
        }

        return $this->redirectToRoute('project.create');
    }

    #[Route('/_health-check')]
    public function healthCheck(EntityManagerInterface $em): Response
    {
        $em->getConnection()->executeQuery('SELECT 1');

        return new Response('HealthChecked');
    }

    /**
     * Update badge for the dashboard's JMonitor card, loaded in its own request so the
     * dashboard itself never waits on an outbound call.
     */
    #[Route('/_version-update', name: 'version.update', condition: "env('APP_EDITION') != 'cloud'")]
    public function versionUpdate(UpdateChecker $updateChecker): Response
    {
        return $this->render('dash/_version_update.html.twig', [
            'status' => $updateChecker->check(),
        ]);
    }
}
