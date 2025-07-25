<?php

namespace App\Entity;

use App\Repository\OnboardingTaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnboardingTaskRepository::class)]
class OnboardingTask
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_WAITING_FOR_DEPENDENCY = 'waiting_for_dependency';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $sortOrder = 0;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PENDING;

    // Fälligkeit
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $dueDaysFromEntry = null;

    // Zuständigkeit
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedEmail = null;

    // E-Mail-Konfiguration
    #[ORM\Column]
    private bool $sendEmail = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $emailTemplate = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $completionToken;

    // Beziehungen
    #[ORM\ManyToOne(targetEntity: Onboarding::class, inversedBy: 'onboardingTasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Onboarding $onboarding = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Role $assignedRole = null;

    // Referenz zur ursprünglichen Vorlage
    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Task $templateTask = null;

    #[ORM\ManyToOne(targetEntity: TaskBlock::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?TaskBlock $taskBlock = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'dependentTasks')]
    #[ORM\JoinTable(name: 'onboarding_task_dependencies')]
    private Collection $dependencies;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'dependencies')]
    private Collection $dependentTasks;

    // Zeitstempel
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailSentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->dependencies = new ArrayCollection();
        $this->dependentTasks = new ArrayCollection();
        $this->completionToken = bin2hex(random_bytes(16));
    }

    // Getter und Setter

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getDueDaysFromEntry(): ?int
    {
        return $this->dueDaysFromEntry;
    }

    public function setDueDaysFromEntry(?int $dueDaysFromEntry): static
    {
        $this->dueDaysFromEntry = $dueDaysFromEntry;

        return $this;
    }

    public function getAssignedEmail(): ?string
    {
        return $this->assignedEmail;
    }

    public function setAssignedEmail(?string $assignedEmail): static
    {
        $this->assignedEmail = $assignedEmail;

        return $this;
    }

    /**
     * Gibt die finale E-Mail-Adresse zurück (direkte Eingabe oder aus Rolle).
     */
    public function getFinalAssignedEmail(): ?string
    {
        if ($this->assignedEmail) {
            return $this->assignedEmail;
        }

        return $this->assignedRole ? $this->assignedRole->getEmail() : null;
    }

    public function isSendEmail(): bool
    {
        return $this->sendEmail;
    }

    public function setSendEmail(bool $sendEmail): static
    {
        $this->sendEmail = $sendEmail;

        return $this;
    }

    public function getEmailTemplate(): ?string
    {
        return $this->emailTemplate;
    }

    public function setEmailTemplate(?string $emailTemplate): static
    {
        $this->emailTemplate = $emailTemplate;

        return $this;
    }

    public function getOnboarding(): ?Onboarding
    {
        return $this->onboarding;
    }

    public function setOnboarding(?Onboarding $onboarding): static
    {
        $this->onboarding = $onboarding;

        return $this;
    }

    public function getAssignedRole(): ?Role
    {
        return $this->assignedRole;
    }

    public function setAssignedRole(?Role $assignedRole): static
    {
        $this->assignedRole = $assignedRole;

        return $this;
    }

    public function getTemplateTask(): ?Task
    {
        return $this->templateTask;
    }

    public function setTemplateTask(?Task $templateTask): static
    {
        $this->templateTask = $templateTask;

        return $this;
    }

    public function getTaskBlock(): ?TaskBlock
    {
        return $this->taskBlock;
    }

    public function setTaskBlock(?TaskBlock $taskBlock): static
    {
        $this->taskBlock = $taskBlock;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDependencies(): Collection
    {
        return $this->dependencies;
    }

    public function addDependency(self $dependency): static
    {
        if (!$this->dependencies->contains($dependency)) {
            $this->dependencies->add($dependency);
        }

        return $this;
    }

    public function removeDependency(self $dependency): static
    {
        $this->dependencies->removeElement($dependency);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDependentTasks(): Collection
    {
        return $this->dependentTasks;
    }

    public function addDependentTask(self $dependentTask): static
    {
        if (!$this->dependentTasks->contains($dependentTask)) {
            $this->dependentTasks->add($dependentTask);
            $dependentTask->addDependency($this);
        }

        return $this;
    }

    public function removeDependentTask(self $dependentTask): static
    {
        if ($this->dependentTasks->removeElement($dependentTask)) {
            $dependentTask->removeDependency($this);
        }

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

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->emailSentAt;
    }

    public function setEmailSentAt(?\DateTimeImmutable $emailSentAt): static
    {
        $this->emailSentAt = $emailSentAt;

        return $this;
    }

    public function getCompletionToken(): string
    {
        return $this->completionToken;
    }

    public function regenerateCompletionToken(): static
    {
        $this->completionToken = bin2hex(random_bytes(16));

        return $this;
    }

    /**
     * Berechnet das effektive Fälligkeitsdatum basierend auf Entry Date.
     */
    public function getEffectiveDueDate(): ?\DateTimeImmutable
    {
        if ($this->dueDate) {
            return $this->dueDate;
        }

        if (null !== $this->dueDaysFromEntry && $this->onboarding) {
            $entryDate = $this->onboarding->getEntryDate();
            if ($entryDate) {
                return $entryDate->modify(sprintf('%+d days', $this->dueDaysFromEntry));
            }
        }

        return null;
    }

    /**
     * Prüft ob die Task überfällig ist.
     */
    public function isOverdue(): bool
    {
        if (self::STATUS_COMPLETED === $this->status) {
            return false;
        }

        $dueDate = $this->getEffectiveDueDate();
        if (!$dueDate) {
            return false;
        }

        return $dueDate < new \DateTimeImmutable();
    }

    /**
     * Markiert die Task als abgeschlossen.
     */
    public function markAsCompleted(): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
