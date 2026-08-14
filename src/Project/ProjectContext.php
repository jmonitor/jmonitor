<?php

declare(strict_types=1);

namespace App\Project;

use App\Entity\Project;
use App\Entity\User;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

class ProjectContext implements ResetInterface
{
    private Project|false|null $project = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {}

    public function getCurrentProject(): ?Project
    {
        if ($this->project !== false) {
            return $this->project;
        }

        $this->project = $this->tryExtractProject() ?: $this->getDefaultProject();

        if ($this->project) {
            $this->storeLastProject($this->project);
        }

        return $this->project;
    }

    /**
     * Forces the current project instead of letting it be guessed: public embed pages,
     * where the viewer has no session, and project-scoped fragments, where guessing
     * would answer for whichever project the session last stored.
     * Callers are responsible for authorization; the membership check is bypassed.
     */
    public function setCurrentProject(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Tries to find a project linked to the logged-in user.
     */
    private function getDefaultProject(): ?Project
    {
        $lastProjectId = $this->requestStack->getSession()->get('last_project_id');

        $project = $lastProjectId ? $this->em->find(Project::class, $lastProjectId) : null;

        if ($project && $this->security->isGranted(ProjectVoter::PROJECT_USER, $project)) {
            return $project;
        }

        foreach ($this->getUser()?->getProjectUsers() ?: [] as $projectUser) {
            return $projectUser->getProject();
        }

        return null;
    }

    private function tryExtractProject(): ?Project
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return null;
        }

        $projectUuid = $request->attributes->get('project');

        if (!$projectUuid) {
            return null;
        }

        $project = $this->em->getRepository(Project::class)->findOneBy([
            'uuid' => $projectUuid,
        ]);

        if (!$project) {
            return null;
        }

        if (!$this->security->isGranted(ProjectVoter::PROJECT_USER, $project)) {
            return null;
        }

        return $project;
    }

    private function storeLastProject(Project $project): void
    {
        $session = $this->requestStack->getSession();

        $session->set('last_project_id', $project->getId());
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function reset(): void
    {
        $this->project = false;
    }
}
