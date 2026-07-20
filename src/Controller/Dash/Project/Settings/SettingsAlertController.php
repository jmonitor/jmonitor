<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project\Settings;

use App\Alerting\AlertMetric;
use App\Entity\Alert;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Repository\AlertRepository;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/settings/alerts')]
#[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
class SettingsAlertController extends AbstractController
{
    #[Route('', name: 'project.settings.alerts')]
    public function alerts(Project $project): Response
    {
        return $this->render('dash/project/settings/alerts/alerts.html.twig');
    }

    #[Route('/create/{component}', name: 'project.settings.alerts.create')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function create(Project $project, Component $component): Response
    {
        return $this->render('dash/project/settings/alerts/create.html.twig', [
            'component' => $component,
        ]);
    }

    #[Route('/edit/{alertMetric}', name: 'project.settings.alerts.edit')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function edit(Project $project, AlertMetric $alertMetric): Response
    {
        return $this->render('dash/project/settings/alerts/create.html.twig', [
            'alertMetric' => $alertMetric,
            'component' => $alertMetric->component(),
        ]);
    }

    #[Route('{alert}/toggle', name: 'project.settings.alerts.toggle')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function toggle(
        Project $project,
        #[MapEntity(mapping: ['alert' => 'uuid'])]
        Alert $alert,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        if ($alert->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $alert->setPaused(!$alert->isPaused());

        $em->flush();

        $this->addFlash('success', 'Alert ' . ($alert->isPaused() ? 'paused' : 'resumed'));

        $referer = $request->headers->get('referer');

        return $this->redirect($referer);
    }

    #[Route('/remove/{alertMetric}', name: 'project.settings.alerts.remove')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function remove(Project $project, AlertMetric $alertMetric, EntityManagerInterface $em, AlertRepository $alertRepository): Response
    {
        $alert = $alertRepository->findOneBy(['project' => $project, 'alertMetric' => $alertMetric]);

        if ($alert) {
            $em->remove($alert);
            $em->flush();

            $this->addFlash('success', 'Alert removed');
        } else {
            $this->addFlash('warning', 'Alert not found');
        }

        return $this->redirectToRoute('project.settings.alerts', [
            'uuid' => $project->getUuid(),
        ]);
    }
}
