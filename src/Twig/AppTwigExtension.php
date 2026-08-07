<?php

declare(strict_types=1);

namespace App\Twig;

use App\Chart\Units\Bytes;
use App\Utils\Units\MilliSecond;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use App\Security\Registration\RegistrationGate;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;

readonly class AppTwigExtension
{
    public function __construct(
        private ProjectContext $projectContext,
        private Security $security,
        private PlanResolver $planResolver,
        private Edition $edition,
        private RegistrationGate $registrationGate,
    ) {}

    #[AsTwigFunction('current_project')]
    public function getCurrentProject(): ?Project
    {
        return $this->projectContext->getCurrentProject();
    }

    #[AsTwigFunction('current_project_user')]
    public function getCurrentProjectUser(): ?ProjectUser
    {
        $user = $this->security->getUser();
        $project = $this->projectContext->getCurrentProject();

        if (!$user instanceof User || !$project) {
            return null;
        }

        foreach ($user->getProjectUsers() as $projectUser) {
            if ($projectUser->getProject() === $project) {
                return $projectUser;
            }
        }

        return null;
    }

    /**
     * Effective plan of a project depending on the edition — replaces
     * project.currentPlan in the templates.
     */
    #[AsTwigFunction('plan')]
    public function getPlan(Project $project): Plan
    {
        return $this->planResolver->resolve($project);
    }

    /**
     * True in the cloud edition — used to gate the cloud-only elements in the templates
     * (billing screens, OAuth sign-in buttons).
     */
    #[AsTwigFunction('is_cloud')]
    public function isCloud(): bool
    {
        return $this->edition->isCloud();
    }

    /**
     * True when an account can be created without an invitation — used to hide the
     * "Register" link on invite-only (self-hosted) instances.
     */
    #[AsTwigFunction('registration_open')]
    public function isRegistrationOpen(): bool
    {
        return $this->registrationGate->isOpen();
    }

    #[AsTwigFilter('bytes')]
    public function getBytes(int|string|null $value, bool $asBinary = true): ?Bytes
    {
        if ($value === null) {
            return null;
        }

        $bytes = is_string($value) ? Bytes::parse($value) : new Bytes($value);

        if ($asBinary) {
            $bytes = $bytes->asBinary();
        }

        return $bytes;
    }

    #[AsTwigFilter('format_bytes', isSafe: ['html'])]
    public function bytesFormat(int|string|null $value, bool $asBinary = true): string
    {
        if ($value === null) {
            return '';
        }

        return $this->getBytes($value, $asBinary)->format();
    }

    #[AsTwigFilter('ms')]
    public function getMilliSecond(int|float|null $value): ?MilliSecond
    {
        if ($value === null) {
            return null;
        }

        return new MilliSecond($value);
    }

    #[AsTwigFilter('format_ms', isSafe: ['html'])]
    public function msFormat(int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return (new MilliSecond($value))->format();
    }

    #[AsTwigTest('scalar')]
    public function isScalar(mixed $value): bool
    {
        return is_scalar($value);
    }
}
