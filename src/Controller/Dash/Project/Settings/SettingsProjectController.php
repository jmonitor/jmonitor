<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project\Settings;

use App\Bridge\InfluxDb\InfluxDb;
use App\Command\Influx\BucketDeletionCommand;
use App\Console\CommandLauncher;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Form\CustomTypes\ProjectNameType;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/settings/project')]
#[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
class SettingsProjectController extends AbstractController
{
    #[Route('', name: 'project.settings.project')]
    public function project(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder(['name' => $project->getName(), 'components' => $project->getComponents()])
            ->add('name', ProjectNameType::class, ['label' => 'New name'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted(ProjectVoter::PROJECT_ADMIN, $project);

            $data = $form->getData();
            $project->setName($data['name']);
            $em->flush();

            $this->addFlash('success', 'Name updated');

            return $this->redirectToRoute('project.settings.project', ['uuid' => $project->getUuid()]);
        }

        return $this->render('dash/project/settings/project/project.html.twig', [
            'project' => $project,
            'components' => Component::alphaOrderedCases(),
            'form' => $form,
        ]);
    }

    #[Route('/delete', name: 'project.delete', methods: ['POST'])]
    #[IsGranted(ProjectVoter::DELETE, subject: 'project')]
    #[IsCsrfTokenValid('delete_project')]
    public function delete(Project $project, EntityManagerInterface $em): Response
    {
        $em->remove($project);
        $em->flush();

        $this->addFlash('success', 'Project deleted');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/toggle_component/{component}', name: 'project.settings.project.toggle_component', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function toggleComponent(Project $project, Component $component, EntityManagerInterface $em): Response
    {
        if ($project->hasComponent($component)) {
            $project->removeComponent($component);
        } else {
            $project->addComponent($component);
        }

        $em->flush();

        $this->addFlash('success', 'Done');

        return $this->redirectToRoute('project.settings.project', ['uuid' => $project->getUuid()]);
    }


    #[Route('/clear_datas', name: 'project.settings.clear_datas')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    #[IsCsrfTokenValid('clear_datas')]
    public function clearDatas(Project $project, EntityManagerInterface $em, CommandLauncher $commandLauncher, InfluxDb $influxDb): Response
    {
        $bucketIdToDelete = $project->getBucketId();

        $newBucket = $influxDb->createBucketForProject($project);
        $project->setBucketId($newBucket->getId());
        $project->setBucketName($newBucket->getName());

        $em->flush();

        $commandLauncher->launchAsync([BucketDeletionCommand::NAME, $bucketIdToDelete]);

        $this->addFlash('success', 'Data deleted');

        return $this->redirectToRoute('project.settings', ['uuid' => $project->getUuid()]);
    }
}
