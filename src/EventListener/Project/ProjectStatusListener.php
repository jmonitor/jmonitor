<?php

declare(strict_types=1);

namespace App\EventListener\Project;

use App\Entity\Enums\ProjectStatus;
use App\Event\PostConsumeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[Lazy]
#[AsEventListener]
readonly class ProjectStatusListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(PostConsumeEvent $event): void
    {
        if ($event->project->getStatus() === ProjectStatus::ACTIVE) {
            return;
        }

        $event->project->setStatus(ProjectStatus::ACTIVE);
        $this->em->flush();

        // TODO push a Mercure update so the frontend can show a flash message
    }
}
