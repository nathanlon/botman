<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'sessions')]
#[ORM\HasLifecycleCallbacks]
class Session
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISCONNECTED = 'disconnected';
    public const STATUS_ABORTED = 'aborted';

    public const END_REASON_SHIFT_COMPLETED = 'shift_completed';
    public const END_REASON_OPERATOR_ENDED = 'operator_ended';
    public const END_REASON_HANDOFF = 'handoff';
    public const END_REASON_DISCONNECTION = 'disconnection';
    public const END_REASON_ADMIN_TERMINATED = 'admin_terminated';
    public const END_REASON_EMERGENCY = 'emergency';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shift::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Shift $shift;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $endReason = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $activeControlSeconds = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $monitoringSeconds = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $interventionCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastHeartbeatAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShift(): Shift
    {
        return $this->shift;
    }

    public function setShift(Shift $shift): static
    {
        $this->shift = $shift;
        return $this;
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

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;
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

    public function getEndReason(): ?string
    {
        return $this->endReason;
    }

    public function setEndReason(?string $endReason): static
    {
        $this->endReason = $endReason;
        return $this;
    }

    public function getActiveControlSeconds(): int
    {
        return $this->activeControlSeconds;
    }

    public function addActiveControlSeconds(int $seconds): static
    {
        $this->activeControlSeconds += $seconds;
        return $this;
    }

    public function getMonitoringSeconds(): int
    {
        return $this->monitoringSeconds;
    }

    public function addMonitoringSeconds(int $seconds): static
    {
        $this->monitoringSeconds += $seconds;
        return $this;
    }

    public function getInterventionCount(): int
    {
        return $this->interventionCount;
    }

    public function incrementInterventionCount(): static
    {
        $this->interventionCount++;
        return $this;
    }

    public function getLastHeartbeatAt(): ?\DateTimeImmutable
    {
        return $this->lastHeartbeatAt;
    }

    public function setLastHeartbeatAt(?\DateTimeImmutable $lastHeartbeatAt): static
    {
        $this->lastHeartbeatAt = $lastHeartbeatAt;
        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        if (!$this->endedAt) {
            return null;
        }
        return $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
