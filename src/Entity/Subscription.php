<?php

namespace App\Entity;

use App\Entity\Enums\Plan;
use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startAt;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 128, unique: true, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column]
    private ?bool $autoRenew = null;

    #[ORM\OneToOne(mappedBy: 'subscription')]
    private ?Project $project = null;

    #[ORM\Column(enumType: Plan::class)]
    private ?Plan $plan = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startAt = new \DateTimeImmutable();
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

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function isAutoRenew(): ?bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        // unset the owning side of the relation if necessary
        if ($project === null && $this->project !== null) {
            $this->project->setSubscription(null);
        }

        // set the owning side of the relation if necessary
        if ($project !== null && $project->getSubscription() !== $this) {
            $project->setSubscription($this);
        }

        $this->project = $project;

        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->endAt < new \DateTimeImmutable('midnight');
    }

    public function nbDaysInPeriod(): int
    {
        return $this->startAt->diff($this->endAt)->days;
    }

    public function nbDaysLeft(): int
    {
        return $this->endAt->diff(new \DateTimeImmutable('midnight'))->days;
    }

    public function __toString(): string
    {
        return $this->plan->label();
    }
}
