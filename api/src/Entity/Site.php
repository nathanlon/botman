<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
#[ORM\HasLifecycleCallbacks]
class Site
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClientOrganization::class, inversedBy: 'sites')]
    #[ORM\JoinColumn(nullable: false)]
    private ClientOrganization $organization;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private string $regionCode;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\Timezone]
    private ?string $timezoneId = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Robot::class, mappedBy: 'site', orphanRemoval: true)]
    private Collection $robots;

    #[ORM\OneToMany(targetEntity: Job::class, mappedBy: 'site')]
    private Collection $jobs;

    public function __construct()
    {
        $this->robots = new ArrayCollection();
        $this->jobs = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getRegionCode(): string
    {
        return $this->regionCode;
    }

    public function setRegionCode(string $regionCode): static
    {
        $this->regionCode = $regionCode;
        return $this;
    }

    public function getTimezoneId(): ?string
    {
        return $this->timezoneId;
    }

    public function setTimezoneId(?string $timezoneId): static
    {
        $this->timezoneId = $timezoneId;
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
            $robot->setSite($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Job>
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }
}
