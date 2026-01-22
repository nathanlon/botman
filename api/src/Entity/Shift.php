<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShiftRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[ORM\Table(name: 'shifts')]
#[ORM\Index(columns: ['start_time_utc', 'end_time_utc'], name: 'idx_shift_times')]
#[ORM\Index(columns: ['status'], name: 'idx_shift_status')]
#[ORM\HasLifecycleCallbacks]
class Shift
{
    public const STATUS_UNASSIGNED = 'unassigned';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MISSED = 'missed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'shifts')]
    #[ORM\JoinColumn(nullable: false)]
    private Job $job;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startTimeUtc;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endTimeUtc;

    #[ORM\ManyToOne(targetEntity: Operator::class, inversedBy: 'shifts')]
    private ?Operator $assignedOperator = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $assignedAt = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_UNASSIGNED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'shift')]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function setJob(Job $job): static
    {
        $this->job = $job;
        return $this;
    }

    public function getStartTimeUtc(): \DateTimeImmutable
    {
        return $this->startTimeUtc;
    }

    public function setStartTimeUtc(\DateTimeImmutable $startTimeUtc): static
    {
        $this->startTimeUtc = $startTimeUtc;
        return $this;
    }

    public function getEndTimeUtc(): \DateTimeImmutable
    {
        return $this->endTimeUtc;
    }

    public function setEndTimeUtc(\DateTimeImmutable $endTimeUtc): static
    {
        $this->endTimeUtc = $endTimeUtc;
        return $this;
    }

    public function getAssignedOperator(): ?Operator
    {
        return $this->assignedOperator;
    }

    public function setAssignedOperator(?Operator $assignedOperator): static
    {
        $this->assignedOperator = $assignedOperator;
        return $this;
    }

    public function getAssignedAt(): ?\DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(?\DateTimeImmutable $assignedAt): static
    {
        $this->assignedAt = $assignedAt;
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

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function getDurationMinutes(): int
    {
        return (int)(($this->endTimeUtc->getTimestamp() - $this->startTimeUtc->getTimestamp()) / 60);
    }

    public function isAssignable(): bool
    {
        return $this->status === self::STATUS_UNASSIGNED;
    }
}
