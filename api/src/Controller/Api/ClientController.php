<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ClientUser;
use App\Entity\Robot;
use App\Entity\Site;
use App\Repository\JobRepository;
use App\Repository\RobotRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/client')]
#[IsGranted('ROLE_CLIENT')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SiteRepository $siteRepository,
        private readonly RobotRepository $robotRepository,
        private readonly JobRepository $jobRepository,
    ) {}

    #[Route('/organization', name: 'api_client_organization', methods: ['GET'])]
    public function getOrganization(): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $org = $user->getOrganization();

        return $this->json([
            'id' => $org->getId(),
            'companyName' => $org->getCompanyName(),
            'countryCode' => $org->getCountryCode(),
            'status' => $org->getStatus(),
            'createdAt' => $org->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/sites', name: 'api_client_sites', methods: ['GET'])]
    public function getSites(): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $sites = $this->siteRepository->findBy(['organization' => $user->getOrganization()]);

        $result = [];
        foreach ($sites as $site) {
            $result[] = [
                'id' => $site->getId(),
                'name' => $site->getName(),
                'address' => $site->getAddress(),
                'regionCode' => $site->getRegionCode(),
                'timezoneId' => $site->getTimezoneId(),
                'status' => $site->getStatus(),
                'robotCount' => $site->getRobots()->count(),
            ];
        }

        return $this->json(['sites' => $result]);
    }

    #[Route('/sites', name: 'api_client_sites_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function createSite(Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $site = new Site();
        $site->setOrganization($user->getOrganization());
        $site->setName($data['name'] ?? '');
        $site->setAddress($data['address'] ?? null);
        $site->setRegionCode($data['regionCode'] ?? '');
        $site->setTimezoneId($data['timezoneId'] ?? null);

        $this->entityManager->persist($site);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Site created',
            'site' => [
                'id' => $site->getId(),
                'name' => $site->getName(),
                'regionCode' => $site->getRegionCode(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/sites/{id}', name: 'api_client_sites_get', methods: ['GET'])]
    public function getSite(int $id): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $site = $this->siteRepository->find($id);

        if (!$site || $site->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $robots = [];
        foreach ($site->getRobots() as $robot) {
            $robots[] = [
                'id' => $robot->getId(),
                'name' => $robot->getName(),
                'model' => $robot->getModel(),
                'status' => $robot->getStatus(),
            ];
        }

        return $this->json([
            'id' => $site->getId(),
            'name' => $site->getName(),
            'address' => $site->getAddress(),
            'regionCode' => $site->getRegionCode(),
            'timezoneId' => $site->getTimezoneId(),
            'status' => $site->getStatus(),
            'robots' => $robots,
        ]);
    }

    #[Route('/sites/{id}', name: 'api_client_sites_update', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function updateSite(int $id, Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $site = $this->siteRepository->find($id);

        if (!$site || $site->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $site->setName($data['name']);
        }
        if (isset($data['address'])) {
            $site->setAddress($data['address']);
        }
        if (isset($data['regionCode'])) {
            $site->setRegionCode($data['regionCode']);
        }
        if (isset($data['timezoneId'])) {
            $site->setTimezoneId($data['timezoneId']);
        }
        if (isset($data['status'])) {
            $site->setStatus($data['status']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Site updated']);
    }

    #[Route('/sites/{siteId}/robots', name: 'api_client_robots_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function createRobot(int $siteId, Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $site = $this->siteRepository->find($siteId);

        if (!$site || $site->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Site not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $robot = new Robot();
        $robot->setSite($site);
        $robot->setName($data['name'] ?? '');
        $robot->setModel($data['model'] ?? '');
        $robot->setCapabilities($data['capabilities'] ?? []);
        $robot->setConnectionEndpoint($data['connectionEndpoint'] ?? null);

        $this->entityManager->persist($robot);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Robot created',
            'robot' => [
                'id' => $robot->getId(),
                'name' => $robot->getName(),
                'model' => $robot->getModel(),
                'status' => $robot->getStatus(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/robots/{id}', name: 'api_client_robots_get', methods: ['GET'])]
    public function getRobot(int $id): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $robot = $this->robotRepository->find($id);

        if (!$robot || $robot->getSite()->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Robot not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $robot->getId(),
            'name' => $robot->getName(),
            'model' => $robot->getModel(),
            'capabilities' => $robot->getCapabilities(),
            'connectionEndpoint' => $robot->getConnectionEndpoint(),
            'status' => $robot->getStatus(),
            'lastSeenAt' => $robot->getLastSeenAt()?->format('c'),
            'site' => [
                'id' => $robot->getSite()->getId(),
                'name' => $robot->getSite()->getName(),
            ],
        ]);
    }

    #[Route('/robots/{id}', name: 'api_client_robots_update', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT_ADMIN')]
    public function updateRobot(int $id, Request $request): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $robot = $this->robotRepository->find($id);

        if (!$robot || $robot->getSite()->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return $this->json(['error' => 'Robot not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $robot->setName($data['name']);
        }
        if (isset($data['model'])) {
            $robot->setModel($data['model']);
        }
        if (isset($data['capabilities'])) {
            $robot->setCapabilities($data['capabilities']);
        }
        if (isset($data['connectionEndpoint'])) {
            $robot->setConnectionEndpoint($data['connectionEndpoint']);
        }
        if (isset($data['status'])) {
            $robot->setStatus($data['status']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Robot updated']);
    }

    #[Route('/jobs', name: 'api_client_jobs', methods: ['GET'])]
    public function getJobs(): JsonResponse
    {
        /** @var ClientUser $user */
        $user = $this->getUser();
        $jobs = $this->jobRepository->findBy(
            ['organization' => $user->getOrganization()],
            ['createdAt' => 'DESC']
        );

        $result = [];
        foreach ($jobs as $job) {
            $result[] = [
                'id' => $job->getId(),
                'title' => $job->getTitle(),
                'status' => $job->getStatus(),
                'startDate' => $job->getStartDate()->format('Y-m-d'),
                'endDate' => $job->getEndDate()->format('Y-m-d'),
                'hourlyRate' => $job->getHourlyRateAmount(),
                'currency' => $job->getHourlyRateCurrency(),
                'site' => [
                    'id' => $job->getSite()->getId(),
                    'name' => $job->getSite()->getName(),
                ],
                'shiftCount' => $job->getShifts()->count(),
            ];
        }

        return $this->json(['jobs' => $result]);
    }
}
