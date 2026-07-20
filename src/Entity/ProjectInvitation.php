<?php

namespace App\Entity;

use App\Entity\Enums\ProjectRole;
use App\Repository\ProjectInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectInvitationRepository::class)]
class ProjectInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16, unique: true)]
    private string $uniquid;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(enumType: ProjectRole::class)]
    private ?ProjectRole $role = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->uniquid = bin2hex(random_bytes(8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower($email);

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getRole(): ?ProjectRole
    {
        return $this->role;
    }

    public function setRole(ProjectRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getUniquid(): string
    {
        return $this->uniquid;
    }
}
