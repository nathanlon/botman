<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\Table(name: 'jobs')]
#[ORM\HasLifecycleCallbacks]
class Job
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClientOrganization::class, inversedBy: 'jobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ClientOrganization $organization;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'jobs')]
    #[ORM\JoinColumn(nullable: false)]
    private Site $site;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::JSON)]
    private array $requiredSkillIds = [];

    #[ORM\Column(type: Types::JSON)]
    private array $requiredCertificationTypeIds = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxLatencyMs = 150;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $hourlyRateAmount;

    #[ORM\Column(length: 3)]
    private string $hourlyRateCurrency = 'USD';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: ClientUser::class)]
    private ?ClientUser $createdBy = null;

    #[ORM\OneToMany(targetEntity: Shift::class, mappedBy: 'job', orphanRemoval: true)]
    private Collection $shifts;

    #[ORM\ManyToMany(targetEntity: Robot::class)]
    #[ORM\JoinTable(name: 'job_robots')]
    private Collection $robots;

    public function __construct()
    {
        $this->shifts = new ArrayCollection();
        $this->robots = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ClientOrganization
    {
        return $this->organization;
    }

    public function setOrganization(ClientOrganization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): static
    {
        $this->site = $site;
        return $this;
    }

    public function getTitle(): string
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

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getRequiredSkillIds(): array
    {
        return $this->requiredSkillIds;
    }

    public function setRequiredSkillIds(array $requiredSkillIds): static
    {
        $this->requiredSkillIds = $requiredSkillIds;
        return $this;
    }

    public function getRequiredCertificationTypeIds(): array
    {
        return $this->requiredCertificationTypeIds;
    }

    public function setRequiredCertificationTypeIds(array $requiredCertificationTypeIds): static
    {
        $this->requiredCertificationTypeIds = $requiredCertificationTypeIds;
        return $this;
    }

    public function getMaxLatencyMs(): int
    {
        return $this->maxLatencyMs;
    }

    public function setMaxLatencyMs(int $maxLatencyMs): static
    {
        $this->maxLatencyMs = $maxLatencyMs;
        return $this;
    }

    public function getHourlyRateAmount(): string
    {
        return $this->hourlyRateAmount;
    }

    public function setHourlyRateAmount(string $hourlyRateAmount): static
    {
        $this->hourlyRateAmount = $hourlyRateAmount;
        return $this;
    }

    public function getHourlyRateCurrency(): string
    {
        return $this->hourlyRateCurrency;
    }

    public function setHourlyRateCurrency(string $hourlyRateCurrency): static
    {
        $this->hourlyRateCurrency = $hourlyRateCurrency;
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

    public function getCreatedBy(): ?ClientUser
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?ClientUser $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, Shift>
     */
    public function getShifts(): Collection
    {
        return $this->shifts;
    }

    /**
     * @return Collection<int, Robot>
     */
    public function getRobots(): Collection
    {
        return $this->robots;
    }

    public function addRobot(Robot $robot): static
    {
        if (!$this->robots->contains($robot)) {
            $this->robots->add($robot);
        }
        return $this;
    }

    public function removeRobot(Robot $robot): static
    {
        $this->robots->removeElement($robot);
        return $this;
    }
}
