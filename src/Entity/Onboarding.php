<?php

namespace App\Entity;

use App\Repository\OnboardingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnboardingRepository::class)]
class Onboarding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $entryDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $team = null;

    #[ORM\Column(length: 255)]
    private string $manager;

    #[ORM\Column(length: 255)]
    private ?string $managerEmail = null;

    #[ORM\Column(length: 255)]
    private string $buddy = '';

    #[ORM\Column(length: 255)]
    private string $buddyEmail = '';

    #[ORM\ManyToOne(targetEntity: OnboardingType::class, inversedBy: 'onboardings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnboardingType $onboardingType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'onboarding', cascade: ['persist', 'remove'])]
    private Collection $tasks;

    /**
     * @var Collection<int, OnboardingTask>
     */
    #[ORM\OneToMany(targetEntity: OnboardingTask::class, mappedBy: 'onboarding', cascade: ['persist', 'remove'])]
    private Collection $onboardingTasks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tasks = new ArrayCollection();
        $this->onboardingTasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function getEntryDate(): ?\DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeImmutable $entryDate): static
    {
        $this->entryDate = $entryDate;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTeam(): ?string
    {
        return $this->team;
    }

    public function setTeam(?string $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getManager(): ?string
    {
        return $this->manager;
    }

    public function setManager(string $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    public function getBuddy(): ?string
    {
        return $this->buddy;
    }

    public function setBuddy(string $buddy): static
    {
        $this->buddy = $buddy;

        return $this;
    }

    public function getManagerEmail(): ?string
    {
        return $this->managerEmail;
    }

    public function setManagerEmail(string $managerEmail): static
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    public function getBuddyEmail(): ?string
    {
        return $this->buddyEmail;
    }

    public function setBuddyEmail(string $buddyEmail): static
    {
        $this->buddyEmail = $buddyEmail;

        return $this;
    }

    public function getOnboardingType(): ?OnboardingType
    {
        return $this->onboardingType;
    }

    public function setOnboardingType(?OnboardingType $onboardingType): static
    {
        $this->onboardingType = $onboardingType;

        return $this;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setOnboarding($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // Set the owning side to null (unless already changed)
            if ($task->getOnboarding() === $this) {
                $task->setOnboarding(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OnboardingTask>
     */
    public function getOnboardingTasks(): Collection
    {
        return $this->onboardingTasks;
    }

    public function addOnboardingTask(OnboardingTask $onboardingTask): static
    {
        if (!$this->onboardingTasks->contains($onboardingTask)) {
            $this->onboardingTasks->add($onboardingTask);
            $onboardingTask->setOnboarding($this);
        }

        return $this;
    }

    public function removeOnboardingTask(OnboardingTask $onboardingTask): static
    {
        if ($this->onboardingTasks->removeElement($onboardingTask)) {
            // Set the owning side to null (unless already changed)
            if ($onboardingTask->getOnboarding() === $this) {
                $onboardingTask->setOnboarding(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
