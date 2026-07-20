<?php

declare(strict_types=1);

namespace App\Entity;

use App\Alerting\AlertMetric;
use App\Metrics\Dto\Bag;
use App\Repository\AlertRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $uuid;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(name: 'metric', enumType: AlertMetric::class)]
    private ?AlertMetric $alertMetric = null;

    #[ORM\Column(nullable: true)]
    private ?array $config = null;
    private ?Bag $configBag = null;

    #[ORM\Column]
    private ?bool $paused = false;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getAlertMetric(): ?AlertMetric
    {
        return $this->alertMetric;
    }

    public function setAlertMetric(AlertMetric $alertMetric): static
    {
        $this->alertMetric = $alertMetric;

        return $this;
    }

    public function getConfig(): ?Bag
    {
        $class = $this->alertMetric?->configBagClass();

        return $class ? ($this->configBag ??= new $class($this->config ?? [])) : null;
    }

    public function setConfig(?Bag $config): void
    {
        $this->config = $config?->all();
        $this->configBag = $config;
    }

    public function isPaused(): ?bool
    {
        return $this->paused;
    }

    public function setPaused(bool $paused): static
    {
        $this->paused = $paused;

        return $this;
    }
}
