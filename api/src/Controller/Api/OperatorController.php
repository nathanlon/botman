<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Operator;
use App\Entity\OperatorSkill;
use App\Entity\OperatorWeeklyAvailability;
use App\Repository\OperatorRepository;
use App\Repository\OperatorSkillRepository;
use App\Repository\OperatorWeeklyAvailabilityRepository;
use App\Repository\ShiftRepository;
use App\Repository\SkillDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/operators')]
#[IsGranted('ROLE_OPERATOR')]
class OperatorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OperatorRepository $operatorRepository,
        private readonly SkillDefinitionRepository $skillDefinitionRepository,
        private readonly OperatorSkillRepository $operatorSkillRepository,
        private readonly OperatorWeeklyAvailabilityRepository $availabilityRepository,
        private readonly ShiftRepository $shiftRepository,
    ) {}

    #[Route('/profile', name: 'api_operator_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        return $this->json([
            'id' => $operator->getId(),
            'email' => $operator->getEmail(),
            'fullName' => $operator->getFullName(),
            'countryCode' => $operator->getCountryCode(),
            'timezoneId' => $operator->getTimezoneId(),
            'status' => $operator->getStatus(),
            'emailVerified' => $operator->isEmailVerified(),
            'createdAt' => $operator->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/profile', name: 'api_operator_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['fullName'])) {
            $operator->setFullName($data['fullName']);
        }
        if (isset($data['countryCode'])) {
            $operator->setCountryCode($data['countryCode']);
        }
        if (isset($data['timezoneId'])) {
            $operator->setTimezoneId($data['timezoneId']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profile updated',
            'operator' => [
                'id' => $operator->getId(),
                'fullName' => $operator->getFullName(),
                'countryCode' => $operator->getCountryCode(),
                'timezoneId' => $operator->getTimezoneId(),
            ]
        ]);
    }

    #[Route('/skills', name: 'api_operator_skills', methods: ['GET'])]
    public function getSkills(): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        $skills = [];
        foreach ($operator->getSkills() as $operatorSkill) {
            $skills[] = [
                'id' => $operatorSkill->getId(),
                'skillId' => $operatorSkill->getSkill()->getId(),
                'skillName' => $operatorSkill->getSkill()->getName(),
                'category' => $operatorSkill->getSkill()->getCategory(),
                'proficiencyLevel' => $operatorSkill->getProficiencyLevel(),
                'addedAt' => $operatorSkill->getAddedAt()->format('c'),
            ];
        }

        return $this->json(['skills' => $skills]);
    }

    #[Route('/skills', name: 'api_operator_skills_add', methods: ['POST'])]
    public function addSkill(Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $skillId = $data['skillId'] ?? null;
        $proficiencyLevel = $data['proficiencyLevel'] ?? 1;

        if (!$skillId) {
            return $this->json(['error' => 'skillId is required'], Response::HTTP_BAD_REQUEST);
        }

        $skill = $this->skillDefinitionRepository->find($skillId);
        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if already has this skill
        $existing = $this->operatorSkillRepository->findOneBy([
            'operator' => $operator,
            'skill' => $skill,
        ]);
        if ($existing) {
            return $this->json(['error' => 'Skill already added'], Response::HTTP_CONFLICT);
        }

        $operatorSkill = new OperatorSkill();
        $operatorSkill->setOperator($operator);
        $operatorSkill->setSkill($skill);
        $operatorSkill->setProficiencyLevel(max(1, min(5, (int)$proficiencyLevel)));

        $this->entityManager->persist($operatorSkill);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Skill added',
            'skill' => [
                'id' => $operatorSkill->getId(),
                'skillId' => $skill->getId(),
                'skillName' => $skill->getName(),
                'proficiencyLevel' => $operatorSkill->getProficiencyLevel(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/skills/{id}', name: 'api_operator_skills_delete', methods: ['DELETE'])]
    public function removeSkill(int $id): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        $operatorSkill = $this->operatorSkillRepository->find($id);
        if (!$operatorSkill || $operatorSkill->getOperator()->getId() !== $operator->getId()) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($operatorSkill);
        $this->entityManager->flush();

        return $this->json(['message' => 'Skill removed']);
    }

    #[Route('/availability', name: 'api_operator_availability', methods: ['GET'])]
    public function getAvailability(): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        $availability = [];
        foreach ($operator->getWeeklyAvailability() as $slot) {
            if ($slot->isCurrentlyEffective()) {
                $availability[] = [
                    'id' => $slot->getId(),
                    'dayOfWeek' => $slot->getDayOfWeek(),
                    'startTime' => $slot->getStartTime()->format('H:i'),
                    'endTime' => $slot->getEndTime()->format('H:i'),
                ];
            }
        }

        return $this->json(['availability' => $availability]);
    }

    #[Route('/availability', name: 'api_operator_availability_set', methods: ['POST'])]
    public function setAvailability(Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $slots = $data['slots'] ?? [];

        // End current availability
        foreach ($operator->getWeeklyAvailability() as $existing) {
            if ($existing->isCurrentlyEffective()) {
                $existing->setEffectiveUntil(new \DateTimeImmutable());
            }
        }

        // Add new availability
        foreach ($slots as $slot) {
            $availability = new OperatorWeeklyAvailability();
            $availability->setOperator($operator);
            $availability->setDayOfWeek((int)($slot['dayOfWeek'] ?? 0));
            $availability->setStartTime(new \DateTimeImmutable($slot['startTime'] ?? '09:00'));
            $availability->setEndTime(new \DateTimeImmutable($slot['endTime'] ?? '17:00'));
            $availability->setEffectiveFrom(new \DateTimeImmutable());

            $this->entityManager->persist($availability);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Availability updated']);
    }

    #[Route('/shifts', name: 'api_operator_shifts', methods: ['GET'])]
    public function getShifts(Request $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = $this->getUser();

        $status = $request->query->get('status');
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $criteria = ['assignedOperator' => $operator];
        if ($status) {
            $criteria['status'] = $status;
        }

        $shifts = $this->shiftRepository->findBy($criteria, ['startTimeUtc' => 'ASC']);

        $result = [];
        foreach ($shifts as $shift) {
            $job = $shift->getJob();
            $result[] = [
                'id' => $shift->getId(),
                'status' => $shift->getStatus(),
                'startTimeUtc' => $shift->getStartTimeUtc()->format('c'),
                'endTimeUtc' => $shift->getEndTimeUtc()->format('c'),
                'durationMinutes' => $shift->getDurationMinutes(),
                'job' => [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'hourlyRate' => $job->getHourlyRateAmount(),
                    'currency' => $job->getHourlyRateCurrency(),
                ],
                'site' => [
                    'id' => $job->getSite()->getId(),
                    'name' => $job->getSite()->getName(),
                ],
            ];
        }

        return $this->json(['shifts' => $result]);
    }
}
