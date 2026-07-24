<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project\Settings;

use App\Entity\Embed;
use App\Entity\Project;
use App\Repository\EmbedRepository;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/settings/embeds')]
#[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
class SettingsEmbedController extends AbstractController
{
    #[Route('', name: 'project.settings.embeds')]
    public function embeds(Project $project, EmbedRepository $embedRepository): Response
    {
        return $this->render('dash/project/settings/embeds/embeds.html.twig', [
            'embeds' => $embedRepository->findBy(['project' => $project], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{token}/delete', name: 'project.settings.embeds.delete', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function delete(
        Project $project,
        #[MapEntity(mapping: ['token' => 'token'])]
        Embed $embed,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        if ($embed->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('embed-action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($embed);
        $em->flush();

        $this->addFlash('success', 'Embed link deleted');

        return $this->redirectToRoute('project.settings.embeds', ['uuid' => $project->getUuid()]);
    }
}
