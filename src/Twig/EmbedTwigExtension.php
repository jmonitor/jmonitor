<?php

declare(strict_types=1);

namespace App\Twig;

use App\Metrics\Dto\EmbedDto;
use App\Project\ProjectContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFilter;

readonly class EmbedTwigExtension
{
    public function __construct(
        private ProjectContext $projectContext,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[AsTwigFilter('embed_url')]
    public function getUrl(EmbedDto $embed): string
    {
        return $this->urlGenerator->generate(
            'embed',
            array_merge($embed->jsonSerialize(), ['uuid' => $this->projectContext->getCurrentProject()->getUuid()]),
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
