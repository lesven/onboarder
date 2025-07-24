<?php

namespace App\Entity;

use App\Repository\BaseTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BaseTypeRepository::class)]
class BaseType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, OnboardingType>
     */
    #[ORM\OneToMany(targetEntity: OnboardingType::class, mappedBy: 'baseType')]
    private Collection $onboardingTypes;

    /**
     * @var Collection<int, TaskBlock>
     */
    #[ORM\OneToMany(targetEntity: TaskBlock::class, mappedBy: 'baseType', cascade: ['persist', 'remove'])]
    private Collection $taskBlocks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->onboardingTypes = new ArrayCollection();
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
     * @return Collection<int, OnboardingType>
     */
    public function getOnboardingTypes(): Collection
    {
        return $this->onboardingTypes;
    }

    public function addOnboardingType(OnboardingType $onboardingType): static
    {
        if (!$this->onboardingTypes->contains($onboardingType)) {
            $this->onboardingTypes->add($onboardingType);
            $onboardingType->setBaseType($this);
        }

        return $this;
    }

    public function removeOnboardingType(OnboardingType $onboardingType): static
    {
        if ($this->onboardingTypes->removeElement($onboardingType)) {
            // Set the owning side to null (unless already changed)
            if ($onboardingType->getBaseType() === $this) {
                $onboardingType->setBaseType(null);
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
            $taskBlock->setBaseType($this);
        }

        return $this;
    }

    public function removeTaskBlock(TaskBlock $taskBlock): static
    {
        if ($this->taskBlocks->removeElement($taskBlock)) {
            // Set the owning side to null (unless already changed)
            if ($taskBlock->getBaseType() === $this) {
                $taskBlock->setBaseType(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
