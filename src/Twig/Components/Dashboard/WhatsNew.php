<?php

declare(strict_types=1);

namespace App\Twig\Components\Dashboard;

use App\Changelog\ChangelogReader;
use App\Changelog\Release;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
readonly class WhatsNew
{
    public function __construct(
        private ChangelogReader $reader,
    ) {}

    /**
     * @return Release[]
     */
    #[ExposeInTemplate]
    public function getReleases(): array
    {
        return $this->reader->read();
    }
}
