<?php

namespace App\Entity;

use App\Repository\OnboardingTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnboardingTypeRepository::class)]
class OnboardingType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: BaseType::class, inversedBy: 'onboardingTypes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?BaseType $baseType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Onboarding>
     */
    #[ORM\OneToMany(targetEntity: Onboarding::class, mappedBy: 'onboardingType')]
    private Collection $onboardings;

    /**
     * @var Collection<int, TaskBlock>
     */
    #[ORM\OneToMany(targetEntity: TaskBlock::class, mappedBy: 'onboardingType', cascade: ['persist', 'remove'])]
    private Collection $taskBlocks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->onboardings = new ArrayCollection();
        $this->taskBlocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBaseType(): ?BaseType
    {
        return $this->baseType;
    }

    public function setBaseType(?BaseType $baseType): static
    {
        $this->baseType = $baseType;

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
     * @return Collection<int, Onboarding>
     */
    public function getOnboardings(): Collection
    {
        return $this->onboardings;
    }

    public function addOnboarding(Onboarding $onboarding): static
    {
        if (!$this->onboardings->contains($onboarding)) {
            $this->onboardings->add($onboarding);
            $onboarding->setOnboardingType($this);
        }

        return $this;
    }

    public function removeOnboarding(Onboarding $onboarding): static
    {
        if ($this->onboardings->removeElement($onboarding)) {
            // Set the owning side to null (unless already changed)
            if ($onboarding->getOnboardingType() === $this) {
                $onboarding->setOnboardingType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TaskBlock>
     */
    public function getTaskBlocks(): Collection
    {
        return $this->taskBlocks;
    }

    public function addTaskBlock(TaskBlock $taskBlock): static
    {
        if (!$this->taskBlocks->contains($taskBlock)) {
            $this->taskBlocks->add($taskBlock);
            $taskBlock->setOnboardingType($this);
        }

        return $this;
    }

    public function removeTaskBlock(TaskBlock $taskBlock): static
    {
        if ($this->taskBlocks->removeElement($taskBlock)) {
            // Set the owning side to null (unless already changed)
            if ($taskBlock->getOnboardingType() === $this) {
                $taskBlock->setOnboardingType(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
