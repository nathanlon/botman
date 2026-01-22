<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Operator;
use App\Entity\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shift>
 */
class ShiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shift::class);
    }

    /**
     * @return Shift[]
     */
    public function findConflictingShifts(
        Operator $operator,
        \DateTimeImmutable $startUtc,
        \DateTimeImmutable $endUtc,
        ?int $excludeShiftId = null
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.assignedOperator = :operator')
            ->andWhere('s.status NOT IN (:excludedStatuses)')
            ->andWhere('s.startTimeUtc < :end')
            ->andWhere('s.endTimeUtc > :start')
            ->setParameter('operator', $operator)
            ->setParameter('excludedStatuses', [Shift::STATUS_CANCELLED, Shift::STATUS_COMPLETED])
            ->setParameter('start', $startUtc)
            ->setParameter('end', $endUtc);

        if ($excludeShiftId) {
            $qb->andWhere('s.id != :excludeId')
               ->setParameter('excludeId', $excludeShiftId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Shift[]
     */
    public function findAvailableShifts(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return $this->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.status = :status')
            ->andWhere('s.startTimeUtc >= :from')
            ->andWhere('s.startTimeUtc <= :to')
            ->andWhere('j.status = :jobStatus')
            ->setParameter('status', Shift::STATUS_UNASSIGNED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('jobStatus', 'open')
            ->orderBy('s.startTimeUtc', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
