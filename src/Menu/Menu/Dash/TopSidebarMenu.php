<?php

declare(strict_types=1);

namespace App\Menu\Menu\Dash;

use App\Menu\Menu\AbstractMenu;
use App\Menu\MenuLink;
use App\Project\ProjectContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 *
 */
#[AsTaggedItem('dash.sidebar.top')]
class TopSidebarMenu extends AbstractMenu
{
    public function __construct(private readonly ProjectContext $projectContext) {}

    public function getLinks(array $options = []): array
    {
        $currentProject = $this->projectContext->getCurrentProject();

        if (!$currentProject) {
            return $this->getNoProjectLinks();
        }

        $links = [];

        $links[] = new MenuLink('Dashboard', 'project.dashboard', ['uuid' => $currentProject->getUuid()], metadatas: [
            'icon' => 'material-symbols:dashboard-outline',
        ]);

        return $links;
    }

    public function getDefaultTemplate(): string
    {
        return 'menu/nav-pills-vertical.html.twig';
    }

    private function getNoProjectLinks(): array
    {
        return [];
    }
}
