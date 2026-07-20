<?php

declare(strict_types=1);

namespace App\Menu\Menu\Dash;

use App\Menu\Menu\AbstractMenu;
use App\Menu\MenuLink;
use App\Project\ProjectContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Request;

#[AsTaggedItem('dash.sidebar.bottom')]
class BottomSidebarMenu extends AbstractMenu
{
    public function __construct(
        private readonly ProjectContext $projectContext,
    ) {}

    public function getLinks(array $options = []): array
    {
        $currentProject = $this->projectContext->getCurrentProject();

        if (!$currentProject) {
            return [];
        }

        $links = [];

        $links[] = new MenuLink('Settings', 'project.settings', ['uuid' => $currentProject->getUuid()], fn(Request $request): bool => str_starts_with((string) $request->attributes->get('_route'), 'project.settings'), metadatas: [
            'icon' => 'material-symbols:settings-outline',
        ]);

        return $links;
    }

    public function getDefaultTemplate(): string
    {
        return 'menu/nav-pills-vertical.html.twig';
    }
}
