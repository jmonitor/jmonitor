<?php

declare(strict_types=1);

namespace App\Menu\Menu\Dash;

use App\Entity\Enums\Component;
use App\Menu\Menu\AbstractMenu;
use App\Menu\MenuLink;
use App\Project\ProjectContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Request;

#[AsTaggedItem('dash.sidebar.metrics')]
class MetricsSidebarMenu extends AbstractMenu
{
    public function __construct(private readonly ProjectContext $projectContext) {}

    public function getLinks(array $options = []): array
    {
        $currentProject = $this->projectContext->getCurrentProject();

        if (!$currentProject) {
            return [];
        }

        $links = [];

        foreach (Component::menuOrderedCases() as $component) {
            if (!$currentProject->hasComponent($component)) {
                continue;
            }

            $links[] = new MenuLink($component->label(), 'project.metrics.component', ['uuid' => $currentProject->getUuid(), 'component' => $component->value], isActive: fn(Request $request): bool => $request->attributes->get('_route') === 'project.metrics.component' && $request->attributes->get('component') === $component->value, metadatas: [
                'icon' => $component->icon(),
            ]);
        }

        return $links;
    }

    public function getDefaultTemplate(): string
    {
        return 'menu/nav-pills-vertical.html.twig';
    }
}
