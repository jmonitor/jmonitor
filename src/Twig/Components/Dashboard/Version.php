<?php

declare(strict_types=1);

namespace App\Twig\Components\Dashboard;

use App\Version\AppVersion;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
readonly class Version
{
    public function __construct(
        private AppVersion $appVersion,
    ) {}

    #[ExposeInTemplate]
    public function getVersion(): string
    {
        return $this->appVersion->get();
    }
}
