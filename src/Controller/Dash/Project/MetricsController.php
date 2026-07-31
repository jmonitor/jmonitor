<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project;

use App\Controller\Attribute\MapEmbedDto;
use App\Entity\Embed;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Entity\User;
use App\Form\Embed\EmbedType;
use App\Metrics\CollectorContext;
use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\MetricsBagProvider;
use App\Plan\PlanResolver;
use App\Repository\EmbedRepository;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    public function embed(
        Project $project,
        Request $request,
        EmbedRepository $embedRepository,
        PlanResolver $planResolver,
        #[MapEmbedDto]
        ?EmbedDto $embedDto = null,
    ): Response {
        $editedEmbed = null;
        $editToken = $request->query->getString('edit');

        if ($editToken !== '') {
            $editedEmbed = $embedRepository->findOneBy(['token' => $editToken, 'project' => $project])
                ?? throw $this->createNotFoundException();
            $embedDto = $editedEmbed->getDto();
        }

        if (!$embedDto) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(EmbedType::class, [
            'renderer' => $embedDto->findRenderer(),
            'autoRefresh' => $embedDto->autoRefresh ? '1' : '',
            'card' => $embedDto->card,
            'chart' => $embedDto->chart,
        ], [
            'metric' => $embedDto->metric,
            'action' => $this->generateUrl('project.metrics.embed', array_filter([
                'uuid' => $project->getUuid(),
                'embed' => $editedEmbed ? null : $embedDto->jsonSerialize(),
                'edit' => $editToken !== '' ? $editToken : null,
            ])),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $metric = $embedDto->metric;
            $renderer = $form->has('renderer') ? $form->get('renderer')->getData() : $metric->availableRenderers()[0];

            $embedDto = new EmbedDto(
                metric: $metric,
                renderer: $renderer,
                autoRefresh: (bool) $form->get('autoRefresh')->getData(),
                card: $form->get('card')->getData(),
                // A submission without a chart subform (e.g. a hand-crafted POST omitting the
                // renderer) still needs its chart options to match the renderer, the same
                // guarantee GaugeEmbedOptions::applyTo() relies on to force displayHelp(false).
                chart: $form->has('chart') ? $form->get('chart')->getData() : EmbedOptionsFactory::createEmpty($renderer),
            );
        }

        $createdEmbed = null;
        $createdToken = $request->query->getString('created');

        if ($createdToken !== '') {
            $createdEmbed = $embedRepository->findOneBy(['token' => $createdToken, 'project' => $project]);
        }

        return $this->render('dash/project/metrics/embed/embed.html.twig', [
            'embed' => $embedDto,
            'form' => $form,
            'createdEmbed' => $createdEmbed,
            'editedEmbed' => $editedEmbed,
            'updated' => $request->query->getBoolean('updated'),
            'canCreateEmbed' => $planResolver->resolve($project)->embedable() && $this->isGranted(ProjectVoter::PROJECT_ADMIN, $project),
        ]);
    }

    #[Route('/embed/create', name: 'project.metrics.embed.create', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function createEmbed(
        Project $project,
        #[MapEmbedDto]
        EmbedDto $embedDto,
        Request $request,
        EntityManagerInterface $em,
        PlanResolver $planResolver,
    ): Response {
        if (!$planResolver->resolve($project)->embedable()) {
            throw $this->createAccessDeniedException('Embeds are not available with the current plan.');
        }

        if (!$this->isCsrfTokenValid('create-embed', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        $embed = new Embed()
            ->setProject($project)
            ->setCreatedBy($user instanceof User ? $user : null)
            ->setDto($embedDto);

        $em->persist($embed);
        $em->flush();

        return $this->redirectToRoute('project.metrics.embed', [
            'uuid' => $project->getUuid(),
            'embed' => $embedDto->jsonSerialize(),
            'created' => $embed->getToken(),
        ]);
    }

    #[Route('/embed/update/{token}', name: 'project.metrics.embed.update', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function updateEmbed(
        Project $project,
        string $token,
        #[MapEmbedDto]
        EmbedDto $embedDto,
        Request $request,
        EmbedRepository $embedRepository,
        EntityManagerInterface $em,
        PlanResolver $planResolver,
    ): Response {
        if (!$planResolver->resolve($project)->embedable()) {
            throw $this->createAccessDeniedException('Embeds are not available with the current plan.');
        }

        if (!$this->isCsrfTokenValid('update-embed', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $embed = $embedRepository->findOneBy(['token' => $token, 'project' => $project])
            ?? throw $this->createNotFoundException();

        if ($embedDto->metric !== $embed->getDto()->metric) {
            throw new BadRequestHttpException('The metric of an embed cannot be changed.');
        }

        $embed->setDto($embedDto);
        $em->flush();

        return $this->redirectToRoute('project.metrics.embed', [
            'uuid' => $project->getUuid(),
            'edit' => $token,
            'updated' => 1,
        ]);
    }
}
