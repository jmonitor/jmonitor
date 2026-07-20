<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project;

use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Form\Embed\EmbedType;
use App\Metrics\CollectorContext;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\MetricsBagProvider;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/metrics')]
class MetricsController extends AbstractController
{
    #[Route('/{component}', name: 'project.metrics.component', priority: -1)]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function component(Project $project, Component $component, MetricsBagProvider $bagProvider, CollectorContext $collectorContext): Response
    {
        $bags = $bagProvider->getComponentBags($component);

        if (empty($bags)) {
            return $this->render('dash/project/metrics/empty.html.twig', [
                'component' => $component,
                'collectorContext' => $collectorContext,
            ]);
        }

        return $this->render('dash/project/metrics/' . $component->value . '.html.twig', [
            'component' => $component,
            'bags' => $bags,
            'bag' => array_values($bags)[0],
        ]);
    }

    #[Route('/embed/', name: 'project.metrics.embed')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function embed(Project $project, #[MapQueryString(key: 'embed')] EmbedDto $embedDto, Request $request): Response
    {
        $form = $this->createForm(EmbedType::class, [
            'metric' => $embedDto->metric,
            'renderer' => $embedDto->renderer,
            'range' => $embedDto->range,
            'autoRefresh' => $embedDto->autoRefresh,
        ], [
            'action' => $this->generateUrl('project.metrics.embed', ['uuid' => $project->getUuid(), 'embed' => $embedDto->jsonSerialize()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $metric = $form->get('metric')->getData();
            $range = $form->has('range') ? $form->get('range')->getData() : null;
            $renderer = $form->has('renderer') ? $form->get('renderer')->getData() : null;
            $autoRefresh = $form->get('autoRefresh')->getData();
            $chartConfig = $form->has('chartConfig') ? $form->get('chartConfig')->getData() : null;
            $embedDto = new EmbedDto($metric, $renderer, $range, $autoRefresh, $chartConfig);
        }

        return $this->render('dash/project/metrics/embed/embed.html.twig', [
            'embed' => $embedDto,
            'form' => $form,
        ]);
    }
}
