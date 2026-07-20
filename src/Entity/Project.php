<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\Component;
use App\Entity\Enums\Plan;
use App\Entity\Enums\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project implements \Stringable
{
    /** Identity of the public read-only demo project (email of the linked demo user + project name). */
    public const string DEMO_EMAIL = 'demo@jmonitor.io';
    public const string DEMO_NAME = 'Demo project';

    /** Well-known uuid of the self-hosted self-monitoring project (identity by convention, like the demo project). */
    public const string SELF_MONITORING_UUID = '98be4322-e585-5c5d-9aa4-c81b0f20b241';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $uuid;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $apiKey;

    /**
     * @var Collection<int, ProjectUser>
     */
    #[ORM\OneToMany(targetEntity: ProjectUser::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $projectUsers;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $bucketId = null;

    #[ORM\Column(length: 13, nullable: true)]
    private ?string $bucketName = null;

    /**
     * @var Collection<int, Alert>
     */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $alerts;

    /**
     * @var string[]
     */
    #[ORM\Column]
    private array $components = [];

    /**
     * @var Collection<int, ProjectInvitation>
     */
    #[ORM\OneToMany(targetEntity: ProjectInvitation::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $invitations;

    #[ORM\Column(enumType: ProjectStatus::class)]
    private ?ProjectStatus $status = ProjectStatus::NEW;

    #[ORM\OneToOne(inversedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Subscription $subscription = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'project')]
    private Collection $orders;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastDataPushDate = null;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->apiKey = bin2hex(random_bytes(16));
        $this->projectUsers = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return ProjectUser[]
     */
    public function getProjectUsers(): array
    {
        return $this->projectUsers->toArray();
    }

    public function addProjectUser(ProjectUser $projectUser): static
    {
        if (!$this->projectUsers->contains($projectUser)) {
            $this->projectUsers->add($projectUser);
            $projectUser->setProject($this);
        }

        return $this;
    }

    public function removeProjectUser(ProjectUser $projectUser): static
    {
        $this->projectUsers->removeElement($projectUser);

        return $this;
    }

    public function getProjectUserByUser(User $user): ?ProjectUser
    {
        foreach ($this->projectUsers as $projectUser) {
            if ($projectUser->getUser() === $user) {
                return $projectUser;
            }
        }

        return null;
    }

    public function getBucketId(): ?string
    {
        return $this->bucketId;
    }

    public function setBucketId(?string $bucketId): static
    {
        $this->bucketId = $bucketId;

        return $this;
    }

    public function getBucketName(): ?string
    {
        return $this->bucketName;
    }

    public function setBucketName(?string $bucketName): static
    {
        $this->bucketName = $bucketName;

        return $this;
    }

    /**
     * @return array<int, Alert>
     */
    public function getAlerts(): array
    {
        return $this->alerts->toArray();
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setProject($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getProject() === $this) {
                $alert->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Alert[]
     */
    public function getAlertsByComponent(Component $component): array
    {
        return array_filter($this->alerts->toArray(), fn(Alert $alert): bool => $alert->getAlertMetric()->component() === $component);
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return array_filter(Component::cases(), fn(Component $component): bool => in_array($component->value, $this->components));
    }

    /**
     * @param Component[] $components
     */
    public function setComponents(array $components): void
    {
        $this->components = array_map(fn(Component $component): string => $component->value, $components);
    }

    public function hasComponent(Component $component): bool
    {
        return in_array($component, $this->getComponents());
    }

    public function removeComponent(Component $component): void
    {
        $this->components = array_filter($this->components, fn(string $value): bool => $value !== $component->value);
    }

    public function addComponent(Component $component): void
    {
        if (!$this->hasComponent($component)) {
            $this->components[] = $component->value;
        }
    }

    /**
     * @return array<int, ProjectInvitation>
     */
    public function getInvitations(): array
    {
        return $this->invitations->toArray();
    }

    public function addInvitation(ProjectInvitation $invitation): static
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setProject($this);
        }

        return $this;
    }

    public function removeInvitation(ProjectInvitation $invitation): static
    {
        if ($this->invitations->removeElement($invitation)) {
            // set the owning side to null (unless already changed)
            if ($invitation->getProject() === $this) {
                $invitation->setProject(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(?ProjectStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSubscription(bool $onlyActive = false): ?Subscription
    {
        if ($onlyActive) {
            return $this->subscription && !$this->subscription->isExpired() ? $this->subscription : null;
        }

        return $this->subscription;
    }

    public function getActiveSubscription(): ?Subscription
    {
        return $this->getSubscription(true);
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setProject($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getProject() === $this) {
                $order->setProject(null);
            }
        }

        return $this;
    }

    /**
     * Plan derived from the active Subscription (cloud logic only).
     *
     * Warning: do not use this to gate a feature or a limit; go through
     * App\Plan\PlanResolver, which takes the edition (APP_EDITION) into account.
     * Only PlanResolver (and the billing logic) should call this method.
     */
    public function getCurrentPlan(): Plan
    {
        return $this->getActiveSubscription()?->getPlan() ?? Plan::FREE;
    }

    /**
     * The public demo project, identified by its name and the email of a linked demo user.
     * Used to suppress side effects on fake data (e.g. alert notifications).
     */
    public function isDemo(): bool
    {
        if ($this->name !== self::DEMO_NAME) {
            return false;
        }

        foreach ($this->projectUsers as $projectUser) {
            if ($projectUser->getUser()->getEmail() === self::DEMO_EMAIL) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getLastDataPushDate(): ?\DateTimeImmutable
    {
        return $this->lastDataPushDate;
    }

    public function setLastDataPushDate(?\DateTimeImmutable $lastDataPushDate): static
    {
        $this->lastDataPushDate = $lastDataPushDate;

        return $this;
    }
}
