<?php

declare(strict_types=1);

namespace App\Twig\Components\Dash;

use App\Project\ProjectContext;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class ApiKeyCopyPaster
{
    public bool $displayFull = false;

    private readonly ProjectContext $projectContext;

    public function __construct(ProjectContext $projectContext)
    {
        $this->projectContext = $projectContext;
    }

    #[ExposeInTemplate]
    public function getKey(): UnicodeString
    {
        return new UnicodeString($this->projectContext->getCurrentProject()->getApiKey());
    }
}
