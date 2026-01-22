<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OperatorWeeklyAvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OperatorWeeklyAvailabilityRepository::class)]
#[ORM\Table(name: 'operator_weekly_availability')]
class OperatorWeeklyAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class, inversedBy: 'weeklyAvailability')]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 0, max: 6)]
    private int $dayOfWeek; // 0=Sunday, 6=Saturday

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $effectiveUntil = null;

    public function __construct()
    {
        $this->effectiveFrom = new \DateTimeImmutable();
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

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getEffectiveFrom(): \DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(\DateTimeImmutable $effectiveFrom): static
    {
        $this->effectiveFrom = $effectiveFrom;
        return $this;
    }

    public function getEffectiveUntil(): ?\DateTimeImmutable
    {
        return $this->effectiveUntil;
    }

    public function setEffectiveUntil(?\DateTimeImmutable $effectiveUntil): static
    {
        $this->effectiveUntil = $effectiveUntil;
        return $this;
    }

    public function isCurrentlyEffective(): bool
    {
        $now = new \DateTimeImmutable();
        if ($now < $this->effectiveFrom) {
            return false;
        }
        if ($this->effectiveUntil !== null && $now > $this->effectiveUntil) {
            return false;
        }
        return true;
    }
}
