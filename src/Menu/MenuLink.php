<?php

declare(strict_types=1);

namespace App\Menu;

use Symfony\Component\HttpFoundation\Request;

// TODO extract an interface, rename this to RouteLink, and add other kinds like UrlLink, ..
class MenuLink
{
    /**
     * @param array<string, mixed> $routeParameters
     * @param mixed[] $metadatas
     */
    public function __construct(
        private string $label,
        private readonly ?string $routeName = null,
        private readonly array $routeParameters = [],
        private readonly ?\Closure $isActive = null,
        private array $metadatas = [],
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function isActive(Request $request): bool
    {
        if ($this->isActive) {
            return call_user_func_array($this->isActive, [$request]);
        }

        if ($this->routeName) {
            return $this->routeName === $request->attributes->get('_route');
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * @return mixed[]
     */
    public function getMetadatas(): array
    {
        return $this->metadatas;
    }

    public function getMetadata(string $name): mixed
    {
        return $this->metadatas[$name] ?? null;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
