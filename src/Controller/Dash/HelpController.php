<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\Project;
use App\Metrics\Metric;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

class HelpController extends AbstractController
{
    #[Route('/help/{uuid:project}/metric/{metric}', name: 'project.help.metric')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function help(Project $project, Metric $metric, Environment $twig): Response
    {
        // a metric can override the default template by creating a template with this name
        if ($twig->getLoader()->exists('dash/help/metric/' . $metric->value . '.html.twig')) {
            return $this->render('dash/help/metric/' . $metric->value . '.html.twig', [
                'metric' => $metric,
            ]);
        }

        return $this->render('dash/help/metric/_default.html.twig', [
            'metric' => $metric,
        ]);
    }

    #[Route('/help/{uuid:project}/item/{item}', name: 'project.help.item')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function helpItem(Project $project, string $item, Environment $twig): Response
    {
        $template = 'dash/help/item/' . $item . '.html.twig';

        if (!$twig->getLoader()->exists($template)) {
            throw $this->createNotFoundException();
        }

        return $this->render($template);
    }
}
