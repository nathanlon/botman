<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ClientUser;
use App\Entity\Job;
use App\Entity\Shift;
use App\Repository\JobRepository;
use App\Repository\RobotRepository;
use App\Repository\ShiftRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/jobs')]
class JobController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JobRepository $jobRepository,
        private readonly SiteRepository $siteRepository,
        private readonly RobotRepository $robotRepository,
        private readonly ShiftRepository $shiftRepository,
    ) {}

    #[Route('', name: 'api_jobs_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function createJob(Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $site = $this->siteRepository->find($data['siteId'] ?? 0);
        if (!$site || $site->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $job = new Job();
        $job->setOrganization($user->getOrganization());
        $job->setSite($site);
        $job->setTitle($data['title'] ?? '');
        $job->setDescription($data['description'] ?? null);
        $job->setStartDate(new \DateTimeImmutable($data['startDate'] ?? 'now'));
        $job->setEndDate(new \DateTimeImmutable($data['endDate'] ?? 'now'));
        $job->setRequiredSkillIds($data['requiredSkillIds'] ?? []);
        $job->setRequiredCertificationTypeIds($data['requiredCertificationTypeIds'] ?? []);
        $job->setMaxLatencyMs($data['maxLatencyMs'] ?? 150);
        $job->setHourlyRateAmount($data['hourlyRateAmount'] ?? '0.00');
        $job->setHourlyRateCurrency($data['hourlyRateCurrency'] ?? 'USD');
        $job->setCreatedBy($user);

        // Add robots
        foreach ($data['robotIds'] ?? [] as $robotId) {
            $robot = $this->robotRepository->find($robotId);
            if ($robot && $robot->getSite()->getId() === $site->getId()) {
                $job->addRobot($robot);
            }
        }

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Job created',
            'job' => [
                'id' => $job->getId(),
                'title' => $job->getTitle(),
                'status' => $job->getStatus(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_jobs_get', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function getJob(int $id): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $job = $this->jobRepository->find($id);

        if (!$job || $job->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        $robots = [];
        foreach ($job->getRobots() as $robot) {
            $robots[] = [
                'id' => $robot->getId(),
                'name' => $robot->getName(),
                'model' => $robot->getModel(),
            ];
        }

        $shifts = [];
        foreach ($job->getShifts() as $shift) {
            $shifts[] = [
                'id' => $shift->getId(),
                'status' => $shift->getStatus(),
                'startTimeUtc' => $shift->getStartTimeUtc()->format('c'),
                'endTimeUtc' => $shift->getEndTimeUtc()->format('c'),
                'assignedOperator' => $shift->getAssignedOperator() ? [
                    'id' => $shift->getAssignedOperator()->getId(),
                    'fullName' => $shift->getAssignedOperator()->getFullName(),
                ] : null,
            ];
        }

        return $this->json([
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
            'status' => $job->getStatus(),
            'startDate' => $job->getStartDate()->format('Y-m-d'),
            'endDate' => $job->getEndDate()->format('Y-m-d'),
            'requiredSkillIds' => $job->getRequiredSkillIds(),
            'requiredCertificationTypeIds' => $job->getRequiredCertificationTypeIds(),
            'maxLatencyMs' => $job->getMaxLatencyMs(),
            'hourlyRateAmount' => $job->getHourlyRateAmount(),
            'hourlyRateCurrency' => $job->getHourlyRateCurrency(),
            'site' => [
                'id' => $job->getSite()->getId(),
                'name' => $job->getSite()->getName(),
            ],
            'robots' => $robots,
            'shifts' => $shifts,
            'createdAt' => $job->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'api_jobs_update', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function updateJob(int $id, Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $job = $this->jobRepository->find($id);

        if (!$job || $job->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $job->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $job->setDescription($data['description']);
        }
        if (isset($data['hourlyRateAmount'])) {
            $job->setHourlyRateAmount($data['hourlyRateAmount']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Job updated']);
    }

    #[Route('/{id}/activate', name: 'api_jobs_activate', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function activateJob(int $id): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $job = $this->jobRepository->find($id);

        if (!$job || $job->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        if ($job->getStatus() !== Job::STATUS_DRAFT) {
            return $this->json(['error' => 'Job can only be activated from draft status'], Response::HTTP_BAD_REQUEST);
        }

        // Generate shifts
        $shifts = $this->generateShiftsForJob($job);
        foreach ($shifts as $shift) {
            $this->entityManager->persist($shift);
        }

        $job->setStatus(Job::STATUS_OPEN);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Job activated',
            'shiftsGenerated' => count($shifts),
        ]);
    }

    private function generateShiftsForJob(Job $job): array
    {
        $shifts = [];
        $shiftDuration = 4 * 60 * 60; // 4 hours in seconds
        $currentDate = $job->getStartDate();
        $endDate = $job->getEndDate();

        while ($currentDate <= $endDate) {
            // Generate 4-hour shifts for 24 hours (6 shifts per day)
            for ($hour = 0; $hour < 24; $hour += 4) {
                $startTime = $currentDate->setTime($hour, 0);
                $endTime = $startTime->modify('+4 hours');

                $shift = new Shift();
                $shift->setJob($job);
                $shift->setStartTimeUtc($startTime);
                $shift->setEndTimeUtc($endTime);

                $shifts[] = $shift;
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $shifts;
    }

    #[Route('/marketplace', name: 'api_jobs_marketplace', methods: ['GET'])]
    #[IsGranted('ROLE_OPERATOR')]
    public function marketplace(Request $request): JsonResponse
    {
        // Get open jobs that operators can apply to
        $jobs = $this->jobRepository->findBy(['status' => Job::STATUS_OPEN], ['createdAt' => 'DESC']);

        $result = [];
        foreach ($jobs as $job) {
            // Count unassigned shifts
            $unassignedShifts = $this->shiftRepository->count([
                'job' => $job,
                'status' => Shift::STATUS_UNASSIGNED,
            ]);

            $result[] = [
                'id' => $job->getId(),
                'title' => $job->getTitle(),
                'description' => $job->getDescription(),
                'startDate' => $job->getStartDate()->format('Y-m-d'),
                'endDate' => $job->getEndDate()->format('Y-m-d'),
                'hourlyRate' => $job->getHourlyRateAmount(),
                'currency' => $job->getHourlyRateCurrency(),
                'requiredSkillIds' => $job->getRequiredSkillIds(),
                'maxLatencyMs' => $job->getMaxLatencyMs(),
                'site' => [
                    'id' => $job->getSite()->getId(),
                    'name' => $job->getSite()->getName(),
                    'regionCode' => $job->getSite()->getRegionCode(),
                ],
                'organization' => [
                    'companyName' => $job->getOrganization()->getCompanyName(),
                ],
                'availableShifts' => $unassignedShifts,
            ];
        }

        return $this->json(['jobs' => $result]);
    }
}
