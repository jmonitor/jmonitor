<?php

declare(strict_types=1);

namespace App\Entity;

use App\Metrics\Dto\EmbedDto;
use App\Repository\EmbedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A published, public, read-only embed link for a single metric card.
 * Config is set at creation and can be edited later; the token is immutable
 * and is the only secret in the URL.
 */
#[ORM\Entity(repositoryClass: EmbedRepository::class)]
class Embed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $token;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /** @var array<string, mixed> EmbedDto::jsonSerialize() shape */
    #[ORM\Column]
    private array $config = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(16));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getDto(): EmbedDto
    {
        return EmbedDto::fromArray($this->config);
    }

    public function setDto(EmbedDto $dto): static
    {
        $this->config = $dto->jsonSerialize();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touchLastUsed(\DateTimeImmutable $when): static
    {
        $this->lastUsedAt = $when;

        return $this;
    }
}
