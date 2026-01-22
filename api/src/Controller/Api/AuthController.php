<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ClientOrganization;
use App\Entity\ClientUser;
use App\Entity\Operator;
use App\Repository\ClientUserRepository;
use App\Repository\OperatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly OperatorRepository $operatorRepository,
        private readonly ClientUserRepository $clientUserRepository,
    ) {}

    #[Route('/register/operator', name: 'api_register_operator', methods: ['POST'])]
    public function registerOperator(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check if email already exists
        if ($this->operatorRepository->findOneBy(['email' => $data['email'] ?? ''])) {
            return $this->json([
                'error' => 'An account with this email already exists. Log in or reset password.'
            ], Response::HTTP_CONFLICT);
        }

        $operator = new Operator();
        $operator->setEmail($data['email'] ?? '');
        $operator->setFullName($data['fullName'] ?? '');
        $operator->setCountryCode($data['countryCode'] ?? '');
        $operator->setTimezoneId($data['timezoneId'] ?? '');

        // Validate password
        $password = $data['password'] ?? '';
        if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
            return $this->json([
                'error' => 'Password must be at least 8 characters with letters and numbers'
            ], Response::HTTP_BAD_REQUEST);
        }

        $operator->setPassword($this->passwordHasher->hashPassword($operator, $password));

        $errors = $this->validator->validate($operator);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($operator);
        $this->entityManager->flush();

        // TODO: Send verification email

        return $this->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'operator' => [
                'id' => $operator->getId(),
                'email' => $operator->getEmail(),
                'fullName' => $operator->getFullName(),
                'status' => $operator->getStatus(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/register/client', name: 'api_register_client', methods: ['POST'])]
    public function registerClient(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check if email already exists
        if ($this->clientUserRepository->findOneBy(['email' => $data['email'] ?? ''])) {
            return $this->json([
                'error' => 'An account with this email already exists.'
            ], Response::HTTP_CONFLICT);
        }

        // Create organization
        $organization = new ClientOrganization();
        $organization->setCompanyName($data['companyName'] ?? '');
        $organization->setCountryCode($data['countryCode'] ?? null);

        // Create admin user
        $clientUser = new ClientUser();
        $clientUser->setOrganization($organization);
        $clientUser->setEmail($data['email'] ?? '');
        $clientUser->setFullName($data['fullName'] ?? '');
        $clientUser->setRole(ClientUser::ROLE_ADMIN);

        // Validate password
        $password = $data['password'] ?? '';
        if (strlen($password) < 8) {
            return $this->json([
                'error' => 'Password must be at least 8 characters'
            ], Response::HTTP_BAD_REQUEST);
        }

        $clientUser->setPassword($this->passwordHasher->hashPassword($clientUser, $password));

        $errors = $this->validator->validate($organization);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages['organization.' . $error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($clientUser);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($organization);
        $this->entityManager->persist($clientUser);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Registration successful.',
            'organization' => [
                'id' => $organization->getId(),
                'companyName' => $organization->getCompanyName(),
            ],
            'user' => [
                'id' => $clientUser->getId(),
                'email' => $clientUser->getEmail(),
                'fullName' => $clientUser->getFullName(),
                'role' => $clientUser->getRole(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user instanceof Operator) {
            return $this->json([
                'type' => 'operator',
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'countryCode' => $user->getCountryCode(),
                'timezoneId' => $user->getTimezoneId(),
                'status' => $user->getStatus(),
                'emailVerified' => $user->isEmailVerified(),
                'roles' => $user->getRoles(),
            ]);
        }

        if ($user instanceof ClientUser) {
            return $this->json([
                'type' => 'client',
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'role' => $user->getRole(),
                'emailVerified' => $user->isEmailVerified(),
                'organization' => [
                    'id' => $user->getOrganization()->getId(),
                    'companyName' => $user->getOrganization()->getCompanyName(),
                ],
                'roles' => $user->getRoles(),
            ]);
        }

        return $this->json(['error' => 'Unknown user type'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
