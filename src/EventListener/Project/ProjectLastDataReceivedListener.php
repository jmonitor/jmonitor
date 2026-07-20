<?php

declare(strict_types=1);

namespace App\EventListener\Project;

use App\Event\PostConsumeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[Lazy]
#[AsEventListener]
readonly class ProjectLastDataReceivedListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(PostConsumeEvent $event): void
    {
        if ($event->project->getLastDataPushDate()?->format('Y-m-d') === date('Y-m-d')) {
            return;
        }

        $event->project->setLastDataPushDate(new \DateTimeImmutable());
        $this->em->flush();
    }
}
