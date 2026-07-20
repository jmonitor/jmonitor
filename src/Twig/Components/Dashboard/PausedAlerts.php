<?php

declare(strict_types=1);

namespace App\Twig\Components\Dashboard;

use App\Project\ProjectContext;
use App\Repository\AlertRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class PausedAlerts
{
    private AlertRepository $alertRepository;
    private ProjectContext $projectContext;

    public function __construct(AlertRepository $alertRepository, ProjectContext $projectContext)
    {
        $this->alertRepository = $alertRepository;
        $this->projectContext = $projectContext;
    }

    #[ExposeInTemplate]
    public function getPausedAlerts(): array
    {
        return $this->alertRepository->findBy([
            'project' => $this->projectContext->getCurrentProject(),
            'paused' => true,
        ]);
    }
}
