<?php

namespace App\Entity;

use App\Entity\Enums\ProjectRole;
use App\Entity\Enums\Role;
use App\Entity\Enums\UserStatus;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $uuid;

    #[ORM\Column(length: 39, nullable: true)]
    private ?string $createdByIp = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 32, enumType: Role::class)]
    private ?Role $role = Role::ROLE_USER;

    /**
     * @var Collection<int, ProjectUser>
     */
    #[ORM\OneToMany(targetEntity: ProjectUser::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $projectUsers;

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\Column(length: 13, unique: true, nullable: true)]
    private ?string $passwordLostHash = null;

    #[ORM\Column(length: 13, nullable: true)]
    private ?string $activationHash = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $subscribedToNewsletter = null;

    #[ORM\Column(nullable: true)]
    private ?bool $subscribedToPartnerMarketing = null;

    #[ORM\Column(nullable: true)]
    private ?bool $subscribedToJmonitorMarketing = null;

    #[ORM\Column(enumType: UserStatus::class)]
    private ?UserStatus $status = UserStatus::ONBOARDING;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastConnectedDate = null;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->projectUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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

    public function isDemo(): bool
    {
        return $this->email === Project::DEMO_EMAIL;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): void
    {
        $this->role = $role;
    }

    public function getRoles(): array
    {
        return [$this->getRole()->value];
    }

    /**
     * @return array<int, ProjectUser>
     */
    public function getProjectUsers(): array
    {
        return $this->projectUsers->toArray();
    }

    public function addProjectUser(ProjectUser $projectUser): static
    {
        if (!$this->projectUsers->contains($projectUser)) {
            $this->projectUsers->add($projectUser);
            $projectUser->setUser($this);
        }

        return $this;
    }

    public function removeProjectUser(ProjectUser $projectUser): static
    {
        $this->projectUsers->removeElement($projectUser);

        return $this;
    }

    public function getRoleInProject(Project $project): ?ProjectRole
    {
        foreach ($this->projectUsers as $projectUser) {
            if ($projectUser->getProject() === $project) {
                return $projectUser->getRole();
            }
        }

        return null;
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

    public function getGravatarHash(): ?string
    {
        return hash('sha256', (string) $this->getEmail());
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    // for easyadmin
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    // for easyadmin
    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPasswordLostHash(): ?string
    {
        return $this->passwordLostHash;
    }

    public function setPasswordLostHash(?string $passwordLostHash): static
    {
        $this->passwordLostHash = $passwordLostHash;

        return $this;
    }

    public function getActivationHash(): ?string
    {
        return $this->activationHash;
    }

    public function setActivationHash(?string $activationHash): static
    {
        $this->activationHash = $activationHash;

        return $this;
    }

    public function getCreatedByIp(): ?string
    {
        return $this->createdByIp;
    }

    public function setCreatedByIp(?string $createdByIp): static
    {
        $this->createdByIp = $createdByIp;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isSubscribedToNewsletter(): ?bool
    {
        return $this->subscribedToNewsletter;
    }

    public function setSubscribedToNewsletter(?bool $subscribedToNewsletter): static
    {
        $this->subscribedToNewsletter = $subscribedToNewsletter;

        return $this;
    }

    public function isSubscribedToPartnerMarketing(): ?bool
    {
        return $this->subscribedToPartnerMarketing;
    }

    public function setSubscribedToPartnerMarketing(?bool $subscribedToPartnerMarketing): static
    {
        $this->subscribedToPartnerMarketing = $subscribedToPartnerMarketing;

        return $this;
    }

    public function isSubscribedToJmonitorMarketing(): ?bool
    {
        return $this->subscribedToJmonitorMarketing;
    }

    public function setSubscribedToJmonitorMarketing(?bool $subscribedToJmonitorMarketing): static
    {
        $this->subscribedToJmonitorMarketing = $subscribedToJmonitorMarketing;

        return $this;
    }

    public function getStatus(): ?UserStatus
    {
        return $this->status;
    }

    public function setStatus(?UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLastConnectedDate(): ?\DateTimeImmutable
    {
        return $this->lastConnectedDate;
    }

    public function setLastConnectedDate(?\DateTimeImmutable $lastConnectedDate): static
    {
        $this->lastConnectedDate = $lastConnectedDate;

        return $this;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email' => $this->email,
            'role' => $this->role,
            'password' => $this->password,
        ];
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return
            $this->id === $user->getId()
            && $this->uuid->toString() === $user->getUuid()->toString()
            && $this->email === $user->getEmail()
            && $this->role === $user->getRole()
            && $this->password === $user->getPassword()
        ;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
