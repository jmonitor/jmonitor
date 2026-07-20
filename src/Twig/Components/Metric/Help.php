<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Help\HelpProvider;
use App\Metrics\Metric;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Makes it easier to standardize how help texts are displayed, especially with sections.
 */
#[AsTwigComponent]
class Help
{
    public Metric $metric;
    private HelpProvider $helpProvider;

    public function __construct(HelpProvider $helpProvider)
    {
        $this->helpProvider = $helpProvider;
    }

    #[ExposeInTemplate]
    public function getHelpDto(): ?\App\Metrics\Help\Dto\Help
    {
        return $this->helpProvider->getHelp($this->metric);
    }
}
