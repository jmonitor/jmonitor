<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\Embed;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, session-less, read-only rendering of a single embedded card.
 * Authorization is the token itself; everything else must 404.
 */
class PublicEmbedController extends AbstractController
{
    #[Route('/e/{token}', name: 'embed.public')]
    public function __invoke(
        #[MapEntity(mapping: ['token' => 'token'])]
        Embed $embed,
        ProjectContext $projectContext,
        PlanResolver $planResolver,
        EntityManagerInterface $em,
        ClockInterface $clock,
    ): Response {
        $project = $embed->getProject();

        if (!$planResolver->resolve($project)->embedable()) {
            throw $this->createNotFoundException();
        }

        $projectContext->setCurrentProject($project);

        try {
            $dto = $embed->getDto();
        } catch (\ValueError) {
            // A stored metric/renderer/range value no longer exists in the enums.
            throw $this->createNotFoundException();
        }

        // Coarse usage tracking; throttled to avoid a write per view.
        if ($embed->getLastUsedAt() === null || $embed->getLastUsedAt() < $clock->now()->modify('-5 minutes')) {
            $embed->touchLastUsed($clock->now());
            $em->flush();
        }

        $response = $this->render('dash/embed/public.html.twig', [
            'embed' => $dto,
        ]);
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
