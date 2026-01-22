<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Operator;
use App\Entity\Session;
use App\Entity\Shift;
use App\Repository\ShiftRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/shifts')]
class ShiftController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShiftRepository $shiftRepository,
        private readonly SessionRepository $sessionRepository,
    ) {}

    #[Route('/{id}', name: 'api_shifts_get', methods: ['GET'])]
    public function getShift(int $id): JsonResponse
    {
        $shift = $this->shiftRepository->find($id);

        if (!$shift) {
            return $this->json(['error' => 'Shift not found'], Response::HTTP_NOT_FOUND);
        }

        $job = $shift->getJob();

        return $this->json([
            'id' => $shift->getId(),
            'status' => $shift->getStatus(),
            'startTimeUtc' => $shift->getStartTimeUtc()->format('c'),
            'endTimeUtc' => $shift->getEndTimeUtc()->format('c'),
            'durationMinutes' => $shift->getDurationMinutes(),
            'assignedOperator' => $shift->getAssignedOperator() ? [
                'id' => $shift->getAssignedOperator()->getId(),
                'fullName' => $shift->getAssignedOperator()->getFullName(),
            ] : null,
            'job' => [
                'id' => $job->getId(),
                'title' => $job->getTitle(),
                'hourlyRate' => $job->getHourlyRateAmount(),
                'currency' => $job->getHourlyRateCurrency(),
            ],
            'site' => [
                'id' => $job->getSite()->getId(),
                'name' => $job->getSite()->getName(),
                'regionCode' => $job->getSite()->getRegionCode(),
            ],
        ]);
    }

    #[Route('/{id}/accept', name: 'api_shifts_accept', methods: ['POST'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function acceptShift(int $id): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $shift = $this->shiftRepository->find($id);

        if (!$shift) {
            return $this->json(['error' => 'Shift not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$shift->isAssignable()) {
            return $this->json(['error' => 'Shift is not available'], Response::HTTP_BAD_REQUEST);
        }

        // Check for conflicts
        $conflicts = $this->shiftRepository->findConflictingShifts(
            $operator,
            $shift->getStartTimeUtc(),
            $shift->getEndTimeUtc()
        );

        if (count($conflicts) > 0) {
            return $this->json(['error' => 'You have a conflicting shift'], Response::HTTP_CONFLICT);
        }

        // Check minimum notice (2 hours)
        $now = new \DateTimeImmutable();
        $minNotice = $shift->getStartTimeUtc()->modify('-2 hours');
        if ($now > $minNotice) {
            return $this->json(['error' => 'Cannot accept shift less than 2 hours before start'], Response::HTTP_BAD_REQUEST);
        }

        // Assign the shift
        $shift->setAssignedOperator($operator);
        $shift->setAssignedAt(new \DateTimeImmutable());
        $shift->setStatus(Shift::STATUS_ASSIGNED);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Shift accepted',
            'shift' => [
                'id' => $shift->getId(),
                'status' => $shift->getStatus(),
                'startTimeUtc' => $shift->getStartTimeUtc()->format('c'),
                'endTimeUtc' => $shift->getEndTimeUtc()->format('c'),
            ]
        ]);
    }

    #[Route('/{id}/cancel', name: 'api_shifts_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function cancelShift(int $id, Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $shift = $this->shiftRepository->find($id);

        if (!$shift) {
            return $this->json(['error' => 'Shift not found'], Response::HTTP_NOT_FOUND);
        }

        if ($shift->getAssignedOperator()?->getId() !== $operator->getId()) {
            return $this->json(['error' => 'This shift is not assigned to you'], Response::HTTP_FORBIDDEN);
        }

        if ($shift->getStatus() !== Shift::STATUS_ASSIGNED) {
            return $this->json(['error' => 'Can only cancel assigned shifts'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'No reason provided';

        // Remove assignment
        $shift->setAssignedOperator(null);
        $shift->setAssignedAt(null);
        $shift->setStatus(Shift::STATUS_UNASSIGNED);

        $this->entityManager->flush();

        // TODO: Trigger emergency coverage if within 24 hours

        return $this->json([
            'message' => 'Shift cancelled',
            'shift' => [
                'id' => $shift->getId(),
                'status' => $shift->getStatus(),
            ]
        ]);
    }

    #[Route('/{id}/start-session', name: 'api_shifts_start_session', methods: ['POST'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function startSession(int $id): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $shift = $this->shiftRepository->find($id);

        if (!$shift) {
            return $this->json(['error' => 'Shift not found'], Response::HTTP_NOT_FOUND);
        }

        if ($shift->getAssignedOperator()?->getId() !== $operator->getId()) {
            return $this->json(['error' => 'This shift is not assigned to you'], Response::HTTP_FORBIDDEN);
        }

        if ($shift->getStatus() !== Shift::STATUS_ASSIGNED) {
            return $this->json(['error' => 'Shift is not in assigned status'], Response::HTTP_BAD_REQUEST);
        }

        // Check if not too early (more than 15 min before start)
        $now = new \DateTimeImmutable();
        $earliestStart = $shift->getStartTimeUtc()->modify('-15 minutes');
        if ($now < $earliestStart) {
            return $this->json(['error' => 'Cannot start session more than 15 minutes before shift'], Response::HTTP_BAD_REQUEST);
        }

        // Create session
        $session = new Session();
        $session->setShift($shift);
        $session->setOperator($operator);
        $session->setLastHeartbeatAt(new \DateTimeImmutable());

        $shift->setStatus(Shift::STATUS_IN_PROGRESS);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Session started',
            'session' => [
                'id' => $session->getId(),
                'status' => $session->getStatus(),
                'startedAt' => $session->getStartedAt()->format('c'),
            ],
            'shift' => [
                'id' => $shift->getId(),
                'status' => $shift->getStatus(),
            ]
        ]);
    }

    #[Route('/{shiftId}/session/{sessionId}/end', name: 'api_shifts_end_session', methods: ['POST'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function endSession(int $shiftId, int $sessionId, Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        $session = $this->sessionRepository->find($sessionId);

        if (!$session || $session->getShift()->getId() !== $shiftId) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        if ($session->getOperator()->getId() !== $operator->getId()) {
            return $this->json(['error' => 'This session is not yours'], Response::HTTP_FORBIDDEN);
        }

        if (!$session->isActive()) {
            return $this->json(['error' => 'Session is not active'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $session->setEndedAt(new \DateTimeImmutable());
        $session->setStatus(Session::STATUS_COMPLETED);
        $session->setEndReason($data['reason'] ?? Session::END_REASON_OPERATOR_ENDED);

        // Update shift status
        $shift = $session->getShift();
        $shift->setStatus(Shift::STATUS_COMPLETED);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Session ended',
            'session' => [
                'id' => $session->getId(),
                'status' => $session->getStatus(),
                'durationSeconds' => $session->getDurationSeconds(),
            ]
        ]);
    }

    #[Route('/available', name: 'api_shifts_available', methods: ['GET'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function availableShifts(Request $request): JsonResponse
    {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $shifts = $this->shiftRepository->findAvailableShifts(
            $from ? new \DateTimeImmutable($from) : new \DateTimeImmutable(),
            $to ? new \DateTimeImmutable($to) : new \DateTimeImmutable('+30 days')
        );

        $result = [];
        foreach ($shifts as $shift) {
            $job = $shift->getJob();
            $result[] = [
                'id' => $shift->getId(),
                'startTimeUtc' => $shift->getStartTimeUtc()->format('c'),
                'endTimeUtc' => $shift->getEndTimeUtc()->format('c'),
                'durationMinutes' => $shift->getDurationMinutes(),
                'job' => [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'hourlyRate' => $job->getHourlyRateAmount(),
                    'currency' => $job->getHourlyRateCurrency(),
                    'requiredSkillIds' => $job->getRequiredSkillIds(),
                ],
                'site' => [
                    'id' => $job->getSite()->getId(),
                    'name' => $job->getSite()->getName(),
                    'regionCode' => $job->getSite()->getRegionCode(),
                ],
            ];
        }

        return $this->json(['shifts' => $result]);
    }
}
