<?php

namespace App\Metrics\Actions\Dto;

class Action
{
    public string $name; // internal name only? actual purpose still unclear
    public ?string $label = null;
    public ?string $icon = null;
    public ?string $url = null;
    public ?string $routeName = null;
    public ?array $routeParams = null;
    public array $attributes = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setRouteName(string $routeName): self
    {
        $this->routeName = $routeName;

        return $this;
    }

    public function setRouteParams(array $routeParams): self
    {
        $this->routeParams = $routeParams;

        return $this;
    }

    public function setAttribute(string $name, ?string $value = null): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }
}
