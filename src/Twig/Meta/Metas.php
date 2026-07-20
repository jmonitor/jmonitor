<?php

declare(strict_types=1);

namespace App\Twig\Meta;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Generates meta tags.
 * https://ogp.me/#types
 */
class Metas implements ResetInterface
{
    /**
     * @var array<array<string, string>>
     */
    private array $metas = [];

    /**
     * @var array<array<string, string>>
     */
    private array $links = [];

    public function __construct(
        private readonly UrlGeneratorInterface $router,
    ) {}

    public function commonMetas(): self
    {
        $this->addOgMeta('og:site_name', 'Jmonitor');
        $this->addOgMeta('og:locale', 'fr_FR');
        $this->addOgMeta('og:type', 'website');

        return $this;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function canonical(string $routeName, array $parameters = []): self
    {
        $url = $this->router->generate($routeName, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->canonicalUrl($url);
    }

    public function canonicalUrl(string $url): self
    {
        $this->links[] = [
            'rel' => 'canonical',
            'href' => $url,
        ];

        $this->addOgMeta('og:url', $url);

        return $this;
    }

    public function robots(string $content): self
    {
        $this->metas[] = [
            'name' => 'robots',
            'content' => $content,
        ];

        return $this;
    }

    public function addOgMeta(string $property, string $content): self
    {
        // indexed by property name so the same og tag is never added twice
        return $this->addMeta(['property' => $property, 'content' => $content], $property);
    }

    /**
     * @return string[][]
     */
    public function all(): array
    {
        $metas = $this->metas;
        $this->metas = [];

        return $metas;
    }

    /**
     * @return string[][]
     */
    public function getLinks(): array
    {
        $links = $this->links;
        $this->links = [];

        return $links;
    }

    /**
     * @param array<string, string> $params
     */
    public function addMeta(array $params, ?string $name = null): self
    {
        if ($name) {
            $this->metas[$name] = $params;
        } else {
            $this->metas[] = $params;
        }

        return $this;
    }

    public function reset(): void
    {
        $this->metas = [];
        $this->links = [];
    }
}
