<?php

declare(strict_types=1);

namespace App\Twig\Components\Alert;

use App\Alerting\AlertMetric;
use App\Entity\Alert;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Form\Alert\AlertType;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class AlertForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public bool $isSuccessful = false;

    #[LiveProp]
    public string $projectUuId;

    #[LiveProp]
    public Component $component;

    #[LiveProp]
    public ?AlertMetric $alertMetric = null;

    private ?Project $project = null;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $em): void
    {
        if (!$this->isGranted(ProjectVoter::PROJECT_ADMIN, $this->getProject())) {
            throw $this->createNotFoundException();
        }

        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var Alert $alert */
        $alert = $this->getForm()->getData();
        $alert->setProject($this->getProject());

        $em->persist($alert);
        $em->flush();

        $this->addFlash('success', 'Alert saved');

        // no redirect on purpose: it would scroll the page back to the top after saving
        $this->isSuccessful = true;
    }

    protected function instantiateForm(): FormInterface
    {
        $alertRepository = $this->em->getRepository(Alert::class);

        if ($this->alertMetric) {
            $alert = $alertRepository->findOneBy([
                'project' => $this->getProject(),
                'alertMetric' => $this->alertMetric,
            ]);
        } else {
            $alert = new Alert();
            $alert->setProject($this->getProject());
        }

        return $this->createForm(AlertType::class, $alert, [
            'component' => $this->component,
        ]);
    }

    private function getProject(): Project
    {
        return $this->project ??= $this->em->getRepository(Project::class)->findOneBy([
            'uuid' => $this->projectUuId,
        ]);
    }
}
