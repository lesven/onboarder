<?php

namespace App\Entity;

use App\Repository\EmailSettingsRepository;
use App\Service\PasswordEncryptionService;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailSettingsRepository::class)]
class EmailSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $smtpHost = null;

    #[ORM\Column]
    private ?int $smtpPort = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    #[ORM\Column(type: 'boolean')]
    private bool $ignoreSslCertificate = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    private ?PasswordEncryptionService $encryptionService = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Setzt den Verschlüsselungsservice (wird durch Dependency Injection gesetzt).
     */
    public function setEncryptionService(PasswordEncryptionService $encryptionService): void
    {
        $this->encryptionService = $encryptionService;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(string $smtpHost): static
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): static
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    public function getSmtpUsername(): ?string
    {
        return $this->smtpUsername;
    }

    public function setSmtpUsername(?string $smtpUsername): static
    {
        $this->smtpUsername = $smtpUsername;

        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        if (null === $this->encryptionService) {
            $this->encryptionService = new PasswordEncryptionService();
        }

        return $this->encryptionService->decrypt($this->smtpPassword);
    }

    public function setSmtpPassword(?string $smtpPassword): static
    {
        if (null === $this->encryptionService) {
            $this->encryptionService = new PasswordEncryptionService();
        }

        $this->smtpPassword = $this->encryptionService->encrypt($smtpPassword);

        return $this;
    }

    public function isIgnoreSslCertificate(): bool
    {
        return $this->ignoreSslCertificate;
    }

    public function setIgnoreSslCertificate(bool $ignoreSslCertificate): static
    {
        $this->ignoreSslCertificate = $ignoreSslCertificate;

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
}
