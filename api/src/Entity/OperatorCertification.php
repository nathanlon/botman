<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OperatorCertificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperatorCertificationRepository::class)]
#[ORM\Table(name: 'operator_certifications')]
class OperatorCertification
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class, inversedBy: 'certifications')]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\ManyToOne(targetEntity: CertificationType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CertificationType $certificationType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificateNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperator(): Operator
    {
        return $this->operator;
    }

    public function setOperator(Operator $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getCertificationType(): CertificationType
    {
        return $this->certificationType;
    }

    public function setCertificationType(CertificationType $certificationType): static
    {
        $this->certificationType = $certificationType;
        return $this;
    }

    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(?string $certificateNumber): static
    {
        $this->certificateNumber = $certificateNumber;
        return $this;
    }

    public function getIssuingAuthority(): ?string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(?string $issuingAuthority): static
    {
        $this->issuingAuthority = $issuingAuthority;
        return $this;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): static
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeImmutable $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return false;
        }
        if ($this->expiryDate === null) {
            return true;
        }
        return $this->expiryDate >= new \DateTimeImmutable('today');
    }
}
