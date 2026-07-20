<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project;

use App\Entity\Project;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}')]
class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'project.settings')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function settings(Project $project): Response
    {
        return $this->redirectToRoute('project.settings.project', ['uuid' => $project->getUuid()]);
    }

    #[Route('/apikey', name: 'project.settings.api_key')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    #[IsCsrfTokenValid('regenerate_api_key', methods: ['POST'])]
    public function apiKey(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $project->setApiKey(bin2hex(random_bytes(16)));

            $em->flush();

            $this->addFlash('success', 'API key regenerated');

            $request->getSession()->set('api_key_just_regenerated', true);

            return $this->redirectToRoute('project.settings.api_key', ['uuid' => $project->getUuid()]);
        }

        $apiKeyJustRegenerated = $request->getSession()->get('api_key_just_regenerated', false);
        $apiKeyJustRegenerated && $request->getSession()->remove('api_key_just_regenerated');

        return $this->render('dash/project/settings/api_key.html.twig', [
            'project' => $project,
            'apiKeyJustRegenerated' => $apiKeyJustRegenerated,
        ]);
    }
}
