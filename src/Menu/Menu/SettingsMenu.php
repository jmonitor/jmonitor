<?php

declare(strict_types=1);

namespace App\Menu\Menu;

use App\Entity\Project;
use App\Menu\MenuLink;
use App\Plan\Edition;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem('project.settings')]
class SettingsMenu extends AbstractMenu
{
    public function __construct(
        private readonly Security $security,
        private readonly Edition $edition,
    ) {}

    public function getLinks(array $options = []): iterable
    {
        /** @var Project $project */
        $project = $options['project'];

        yield new MenuLink('Project', 'project.settings.project', ['uuid' => $project->getUuid()], metadatas: [
            'icon' => 'mdi:coffee-outline',
        ]);
        yield new MenuLink('Alerts', 'project.settings.alerts', ['uuid' => $project->getUuid()], metadatas: [
            'icon' => 'material-symbols:notifications-outline',
        ]);
        yield new MenuLink('Team', 'project.settings.team', ['uuid' => $project->getUuid()], metadatas: [
            'icon' => 'mdi:user-outline',
        ]);
        yield new MenuLink('Embeds', 'project.settings.embeds', ['uuid' => $project->getUuid()], metadatas: [
            'icon' => 'material-symbols:code-rounded',
        ]);
        yield new MenuLink('API key', 'project.settings.api_key', ['uuid' => $project->getUuid()], metadatas: [
            'icon' => 'material-symbols:vpn-key-outline',
            'disabled' => !$this->security->isGranted(ProjectVoter::PROJECT_ADMIN, $project),
        ]);
        if ($this->edition->isCloud()) {
            yield new MenuLink('Plan', 'project.settings.plan', ['uuid' => $project->getUuid()], metadatas: [
                'icon' => 'material-symbols:rocket-launch-outline',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('project');
    }

    public function getDefaultTemplate(): string
    {
        return 'menu/underline.html.twig';
    }
}
