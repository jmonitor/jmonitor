<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\Project;
use App\Metrics\Help\HelpProvider;
use App\Metrics\Metric;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

class HelpController extends AbstractController
{
    private const string DEFAULT_TEMPLATE = 'dash/help/metric/_default.html.twig';

    #[Route('/help/{uuid:project}/metric/{metric}', name: 'project.help.metric')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function help(Project $project, Metric $metric, Environment $twig): Response
    {
        return $this->render($this->metricTemplate($metric, $twig), [
            'metric' => $metric,
        ]);
    }

    /**
     * Anonymous, project-less rendering of the same help, framed by third-party back-offices
     * (jmonitor/easyadmin-bundle) or opened alone in a tab.
     */
    #[Route('/help/metric/{metric}', name: 'help.metric.public')]
    public function publicHelp(Metric $metric, Request $request, Environment $twig, HelpProvider $helpProvider): Response
    {
        $template = $this->metricTemplate($metric, $twig);

        if ($template === self::DEFAULT_TEMPLATE && $helpProvider->getHelp($metric) === null) {
            throw $this->createNotFoundException();
        }

        $theme = $request->query->all()['theme'] ?? null;

        return $this->render($template, [
            'metric' => $metric,
            'standalone' => true,
            'theme' => in_array($theme, ['light', 'dark'], true) ? $theme : null,
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

    /**
     * A metric overrides the default help template by providing one named after its key.
     */
    private function metricTemplate(Metric $metric, Environment $twig): string
    {
        $template = 'dash/help/metric/' . $metric->value . '.html.twig';

        return $twig->getLoader()->exists($template) ? $template : self::DEFAULT_TEMPLATE;
    }
}
