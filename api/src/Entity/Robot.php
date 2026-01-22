<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RobotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RobotRepository::class)]
#[ORM\Table(name: 'robots')]
#[ORM\HasLifecycleCallbacks]
class Robot
{
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ONLINE = 'online';
    public const STATUS_IN_SESSION = 'in_session';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_ERROR = 'error';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'robots')]
    #[ORM\JoinColumn(nullable: false)]
    private Site $site;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $model;

    #[ORM\Column(type: Types::JSON)]
    private array $capabilities = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $connectionEndpoint = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OFFLINE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

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

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): static
    {
        $this->site = $site;
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

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function setCapabilities(array $capabilities): static
    {
        $this->capabilities = $capabilities;
        return $this;
    }

    public function getConnectionEndpoint(): ?string
    {
        return $this->connectionEndpoint;
    }

    public function setConnectionEndpoint(?string $connectionEndpoint): static
    {
        $this->connectionEndpoint = $connectionEndpoint;
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

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
