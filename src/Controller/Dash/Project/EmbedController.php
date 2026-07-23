<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project;

use App\Entity\Project;
use App\Metrics\Dto\EmbedDto;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EmbedController extends AbstractController
{
    // /p/.../embed?m=disk_usage&re=line&ra=last_1_hour&ar=1m
    #[Route('/p/{uuid:project}/embed', name: 'embed')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function embed(Project $project, #[MapQueryString] EmbedDto $embed): Response
    {
        return $this->render('dash/embed/embed.html.twig', [
            'embed' => $embed,
        ]);
    }
}
