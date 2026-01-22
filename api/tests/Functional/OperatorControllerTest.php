<?php

namespace App\Tests\Functional;

use App\Entity\Operator;
use App\Entity\OperatorSkill;
use App\Entity\OperatorWeeklyAvailability;
use App\Entity\SkillDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OperatorControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private ?Operator $testOperator = null;
    private ?string $authToken = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createTestOperator();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createTestOperator(): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->testOperator = new Operator();
        $this->testOperator->setEmail('operator' . uniqid() . '@test.com');
        $this->testOperator->setFirstName('Test');
        $this->testOperator->setLastName('Operator');
        $this->testOperator->setStatus('active');
        $this->testOperator->setRegionCode('US-CA');
        $this->testOperator->setTimezoneId('America/Los_Angeles');

        $hashedPassword = $passwordHasher->hashPassword($this->testOperator, 'TestPass123!');
        $this->testOperator->setPasswordHash($hashedPassword);

        $this->entityManager->persist($this->testOperator);
        $this->entityManager->flush();

        // Generate a mock token (in real tests, you'd use actual JWT)
        $this->authToken = 'Bearer test_token_' . $this->testOperator->getId();
    }

    private function getAuthHeaders(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => $this->authToken,
        ];
    }

    public function testGetProfileUnauthenticated(): void
    {
        $this->client->request('GET', '/api/operator/profile');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateProfileSuccess(): void
    {
        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'PUT',
            '/api/operator/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'firstName' => 'Updated',
                'lastName' => 'Name',
                'regionCode' => 'US-NY',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Profile updated', $response['message']);
    }

    public function testGetAvailability(): void
    {
        // Create availability
        $availability = new OperatorWeeklyAvailability();
        $availability->setOperator($this->testOperator);
        $availability->setDayOfWeek(1); // Monday
        $availability->setStartTime(new \DateTime('09:00'));
        $availability->setEndTime(new \DateTime('17:00'));
        $this->entityManager->persist($availability);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('GET', '/api/operator/availability');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('availability', $response);
        $this->assertCount(1, $response['availability']);
        $this->assertEquals(1, $response['availability'][0]['dayOfWeek']);
    }

    public function testSetAvailability(): void
    {
        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'PUT',
            '/api/operator/availability',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'slots' => [
                    [
                        'dayOfWeek' => 1,
                        'startTime' => '09:00',
                        'endTime' => '17:00',
                    ],
                    [
                        'dayOfWeek' => 2,
                        'startTime' => '10:00',
                        'endTime' => '18:00',
                    ],
                ],
            ])
        );

        $this->assertResponseIsSuccessful();

        // Verify in database
        $availability = $this->entityManager->getRepository(OperatorWeeklyAvailability::class)
            ->findBy(['operator' => $this->testOperator]);
        $this->assertCount(2, $availability);
    }

    public function testSetAvailabilityWithInvalidTime(): void
    {
        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'PUT',
            '/api/operator/availability',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'slots' => [
                    [
                        'dayOfWeek' => 1,
                        'startTime' => '17:00',
                        'endTime' => '09:00', // End before start
                    ],
                ],
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetSkills(): void
    {
        // Create a skill definition
        $skillDef = new SkillDefinition();
        $skillDef->setName('Robot Operation');
        $skillDef->setCategory('Operations');
        $this->entityManager->persist($skillDef);

        // Assign skill to operator
        $operatorSkill = new OperatorSkill();
        $operatorSkill->setOperator($this->testOperator);
        $operatorSkill->setSkillDefinition($skillDef);
        $operatorSkill->setProficiencyLevel(4);
        $this->entityManager->persist($operatorSkill);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('GET', '/api/operator/skills');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('skills', $response);
        $this->assertCount(1, $response['skills']);
        $this->assertEquals('Robot Operation', $response['skills'][0]['skillName']);
        $this->assertEquals(4, $response['skills'][0]['proficiencyLevel']);
    }

    public function testAddSkill(): void
    {
        // Create a skill definition
        $skillDef = new SkillDefinition();
        $skillDef->setName('Forklift Operation');
        $skillDef->setCategory('Equipment');
        $this->entityManager->persist($skillDef);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'POST',
            '/api/operator/skills',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'skillId' => $skillDef->getId(),
                'proficiencyLevel' => 3,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Verify in database
        $skill = $this->entityManager->getRepository(OperatorSkill::class)
            ->findOneBy(['operator' => $this->testOperator, 'skillDefinition' => $skillDef]);
        $this->assertNotNull($skill);
        $this->assertEquals(3, $skill->getProficiencyLevel());
    }

    public function testAddDuplicateSkill(): void
    {
        // Create a skill definition
        $skillDef = new SkillDefinition();
        $skillDef->setName('Warehouse Management');
        $skillDef->setCategory('Management');
        $this->entityManager->persist($skillDef);

        // Already assign skill
        $operatorSkill = new OperatorSkill();
        $operatorSkill->setOperator($this->testOperator);
        $operatorSkill->setSkillDefinition($skillDef);
        $operatorSkill->setProficiencyLevel(2);
        $this->entityManager->persist($operatorSkill);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'POST',
            '/api/operator/skills',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'skillId' => $skillDef->getId(),
                'proficiencyLevel' => 3,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testRemoveSkill(): void
    {
        // Create and assign a skill
        $skillDef = new SkillDefinition();
        $skillDef->setName('Safety Training');
        $skillDef->setCategory('Safety');
        $this->entityManager->persist($skillDef);

        $operatorSkill = new OperatorSkill();
        $operatorSkill->setOperator($this->testOperator);
        $operatorSkill->setSkillDefinition($skillDef);
        $operatorSkill->setProficiencyLevel(5);
        $this->entityManager->persist($operatorSkill);
        $this->entityManager->flush();

        $skillId = $operatorSkill->getId();

        $this->client->loginUser($this->testOperator);

        $this->client->request('DELETE', '/api/operator/skills/' . $skillId);

        $this->assertResponseIsSuccessful();

        // Verify removal
        $this->entityManager->clear();
        $removedSkill = $this->entityManager->getRepository(OperatorSkill::class)->find($skillId);
        $this->assertNull($removedSkill);
    }

    public function testGetShiftsEmpty(): void
    {
        $this->client->loginUser($this->testOperator);

        $this->client->request('GET', '/api/operator/shifts');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('shifts', $response);
        $this->assertCount(0, $response['shifts']);
    }
}
