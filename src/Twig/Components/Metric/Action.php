<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Actions\Dto\Action as ActionDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class Action
{
    public ActionDto $action;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    #[ExposeInTemplate]
    public function getUrl(): ?string
    {
        if ($this->action->url) {
            return $this->action->url;
        }

        if ($this->action->routeName) {
            return $this->urlGenerator->generate($this->action->routeName, $this->action->routeParams ?? []);
        }

        return null;
    }

    #[ExposeInTemplate]
    public function getAttributes(): string
    {
        $attributes = '';

        foreach ($this->action->attributes as $key => $value) {
            if (is_string($value)) {
                $attributes .= ' ' . $key . '="' . $value . '"';
            } else {
                $attributes .= ' ' . $key;
            }
        }

        return $attributes;
    }
}
