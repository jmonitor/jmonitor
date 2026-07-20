<?php

namespace App\Twig\Components;

use App\Metrics\Metric;
use App\Project\ProjectContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class HelpIcon
{
    public ?string $url = null;
    public ?string $item = null;
    public ?Metric $metric = null;

    private readonly UrlGeneratorInterface $urlGenerator;
    private readonly ProjectContext $projectContext;

    public function __construct(UrlGeneratorInterface $urlGenerator, ProjectContext $projectContext)
    {
        $this->urlGenerator = $urlGenerator;
        $this->projectContext = $projectContext;
    }

    #[ExposeInTemplate]
    public function getUrl(): string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->metric) {
            return $this->urlGenerator->generate('project.help.metric', ['uuid' => $this->projectContext->getCurrentProject()->getUuid(), 'metric' => $this->metric->value]);
        }

        return $this->urlGenerator->generate('project.help.item', ['uuid' => $this->projectContext->getCurrentProject()->getUuid(), 'item' => $this->item]);
    }
}
