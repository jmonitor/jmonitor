<?php

namespace App\Entity;

use App\Entity\Enums\ProjectRole;
use App\Repository\ProjectUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectUserRepository::class)]
class ProjectUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'projectUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'projectUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\Column(length: 32, enumType: ProjectRole::class)]
    private ProjectRole $role;

    #[ORM\Column]
    private ?bool $alertNotificationsEnabled = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getRole(): ProjectRole
    {
        return $this->role;
    }

    public function setRole(ProjectRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isAlertNotificationsEnabled(): ?bool
    {
        return $this->alertNotificationsEnabled;
    }

    public function setAlertNotificationsEnabled(bool $alertNotificationsEnabled): static
    {
        $this->alertNotificationsEnabled = $alertNotificationsEnabled;

        return $this;
    }
}
