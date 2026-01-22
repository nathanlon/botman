<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OperatorSkillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OperatorSkillRepository::class)]
#[ORM\Table(name: 'operator_skills')]
#[ORM\UniqueConstraint(name: 'operator_skill_unique', columns: ['operator_id', 'skill_id'])]
class OperatorSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\ManyToOne(targetEntity: SkillDefinition::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SkillDefinition $skill;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5)]
    private int $proficiencyLevel = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $addedAt;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
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

    public function getSkill(): SkillDefinition
    {
        return $this->skill;
    }

    public function setSkill(SkillDefinition $skill): static
    {
        $this->skill = $skill;
        return $this;
    }

    public function getProficiencyLevel(): int
    {
        return $this->proficiencyLevel;
    }

    public function setProficiencyLevel(int $proficiencyLevel): static
    {
        $this->proficiencyLevel = $proficiencyLevel;
        return $this;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
