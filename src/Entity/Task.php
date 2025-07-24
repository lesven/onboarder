<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_WAITING_FOR_DEPENDENCY = 'waiting_for_dependency';

    public const EMAIL_TRIGGER_IMMEDIATE = 'immediate';
    public const EMAIL_TRIGGER_FIXED_DATE = 'fixed_date';
    public const EMAIL_TRIGGER_RELATIVE_DATE = 'relative_date';
    public const EMAIL_TRIGGER_MANUAL = 'manual';

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

    // E-Mail-Konfiguration
    #[ORM\Column(length: 50)]
    private string $emailTrigger = self::EMAIL_TRIGGER_MANUAL;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailSendDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $emailSendDaysFromEntry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $emailTemplate = null;

    // Erinnerungsmail
    #[ORM\Column]
    private bool $hasReminder = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderSendDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $reminderSendDaysFromEntry = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reminderTemplate = null;

    // Beziehungen
    #[ORM\ManyToOne(targetEntity: TaskBlock::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TaskBlock $taskBlock = null;

    #[ORM\ManyToOne(targetEntity: Onboarding::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Onboarding $onboarding = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Role $assignedRole = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'dependentTasks')]
    #[ORM\JoinTable(name: 'task_dependencies')]
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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->dependencies = new ArrayCollection();
        $this->dependentTasks = new ArrayCollection();
    }

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

    public function isCompleted(): bool
    {
        return self::STATUS_COMPLETED === $this->status;
    }

    public function markAsCompleted(): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();

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

    /**
     * Berechnet das finale Fälligkeitsdatum basierend auf Eintrittsdatum oder festem Datum.
     */
    public function getCalculatedDueDate(): ?\DateTimeImmutable
    {
        if ($this->dueDate) {
            return $this->dueDate;
        }

        if (null !== $this->dueDaysFromEntry && $this->onboarding && $this->onboarding->getEntryDate()) {
            return $this->onboarding->getEntryDate()->modify('+'.$this->dueDaysFromEntry.' days');
        }

        return null;
    }

    public function getEmailTrigger(): string
    {
        return $this->emailTrigger;
    }

    public function setEmailTrigger(string $emailTrigger): static
    {
        $this->emailTrigger = $emailTrigger;

        return $this;
    }

    public function getEmailSendDate(): ?\DateTimeImmutable
    {
        return $this->emailSendDate;
    }

    public function setEmailSendDate(?\DateTimeImmutable $emailSendDate): static
    {
        $this->emailSendDate = $emailSendDate;

        return $this;
    }

    public function getEmailSendDaysFromEntry(): ?int
    {
        return $this->emailSendDaysFromEntry;
    }

    public function setEmailSendDaysFromEntry(?int $emailSendDaysFromEntry): static
    {
        $this->emailSendDaysFromEntry = $emailSendDaysFromEntry;

        return $this;
    }

    /**
     * Berechnet das finale E-Mail-Versanddatum.
     */
    public function getCalculatedEmailSendDate(): ?\DateTimeImmutable
    {
        switch ($this->emailTrigger) {
            case self::EMAIL_TRIGGER_IMMEDIATE:
                return $this->onboarding ? $this->onboarding->getCreatedAt() : new \DateTimeImmutable();
            case self::EMAIL_TRIGGER_FIXED_DATE:
                return $this->emailSendDate;
            case self::EMAIL_TRIGGER_RELATIVE_DATE:
                if (null !== $this->emailSendDaysFromEntry && $this->onboarding && $this->onboarding->getEntryDate()) {
                    return $this->onboarding->getEntryDate()->modify('+'.$this->emailSendDaysFromEntry.' days');
                }
                break;
            case self::EMAIL_TRIGGER_MANUAL:
            default:
                return null;
        }

        return null;
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

    public function getEmailTemplate(): ?string
    {
        return $this->emailTemplate;
    }

    public function setEmailTemplate(?string $emailTemplate): static
    {
        $this->emailTemplate = $emailTemplate;

        return $this;
    }

    public function isHasReminder(): bool
    {
        return $this->hasReminder;
    }

    public function setHasReminder(bool $hasReminder): static
    {
        $this->hasReminder = $hasReminder;

        return $this;
    }

    public function getReminderSendDate(): ?\DateTimeImmutable
    {
        return $this->reminderSendDate;
    }

    public function setReminderSendDate(?\DateTimeImmutable $reminderSendDate): static
    {
        $this->reminderSendDate = $reminderSendDate;

        return $this;
    }

    public function getReminderSendDaysFromEntry(): ?int
    {
        return $this->reminderSendDaysFromEntry;
    }

    public function setReminderSendDaysFromEntry(?int $reminderSendDaysFromEntry): static
    {
        $this->reminderSendDaysFromEntry = $reminderSendDaysFromEntry;

        return $this;
    }

    public function getReminderTemplate(): ?string
    {
        return $this->reminderTemplate;
    }

    public function setReminderTemplate(?string $reminderTemplate): static
    {
        $this->reminderTemplate = $reminderTemplate;

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

    /**
     * Prüft ob alle Abhängigkeiten erfüllt sind.
     */
    public function areDependenciesSatisfied(): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!$dependency->isCompleted()) {
                return false;
            }
        }

        return true;
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

    public function getReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderSentAt;
    }

    public function setReminderSentAt(?\DateTimeImmutable $reminderSentAt): static
    {
        $this->reminderSentAt = $reminderSentAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function hasReminder(): ?bool
    {
        return $this->hasReminder;
    }
}
