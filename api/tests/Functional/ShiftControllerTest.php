<?php

namespace App\Tests\Functional;

use App\Entity\ClientOrganization;
use App\Entity\ClientUser;
use App\Entity\Job;
use App\Entity\Operator;
use App\Entity\Robot;
use App\Entity\Session;
use App\Entity\Shift;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ShiftControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private ?Operator $testOperator = null;
    private ?ClientOrganization $testOrganization = null;
    private ?Site $testSite = null;
    private ?Robot $testRobot = null;
    private ?Job $testJob = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createTestEntities();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createTestEntities(): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create organization
        $this->testOrganization = new ClientOrganization();
        $this->testOrganization->setCompanyName('Shift Test Corp ' . uniqid());
        $this->testOrganization->setStatus('active');
        $this->entityManager->persist($this->testOrganization);

        // Create site
        $this->testSite = new Site();
        $this->testSite->setOrganization($this->testOrganization);
        $this->testSite->setName('Shift Test Site');
        $this->testSite->setRegionCode('US-CA');
        $this->testSite->setStatus('active');
        $this->testSite->setTimezoneId('America/Los_Angeles');
        $this->entityManager->persist($this->testSite);

        // Create robot
        $this->testRobot = new Robot();
        $this->testRobot->setSite($this->testSite);
        $this->testRobot->setName('Shift Test Robot');
        $this->testRobot->setModel('Universal-UR10');
        $this->testRobot->setStatus('online');
        $this->entityManager->persist($this->testRobot);

        // Create job
        $this->testJob = new Job();
        $this->testJob->setOrganization($this->testOrganization);
        $this->testJob->setSite($this->testSite);
        $this->testJob->setRobot($this->testRobot);
        $this->testJob->setTitle('Shift Test Job');
        $this->testJob->setStartDate(new \DateTime('-1 day'));
        $this->testJob->setEndDate(new \DateTime('+30 days'));
        $this->testJob->setHourlyRateAmount(25.00);
        $this->testJob->setHourlyRateCurrency('USD');
        $this->testJob->setMaxLatencyMs(150);
        $this->testJob->setStatus('active');
        $this->entityManager->persist($this->testJob);

        // Create operator
        $this->testOperator = new Operator();
        $this->testOperator->setEmail('shiftoperator' . uniqid() . '@test.com');
        $this->testOperator->setFirstName('Shift');
        $this->testOperator->setLastName('Operator');
        $this->testOperator->setStatus('active');
        $this->testOperator->setRegionCode('US-CA');
        $this->testOperator->setTimezoneId('America/Los_Angeles');

        $hashedPassword = $passwordHasher->hashPassword($this->testOperator, 'TestPass123!');
        $this->testOperator->setPasswordHash($hashedPassword);

        $this->entityManager->persist($this->testOperator);
        $this->entityManager->flush();
    }

    public function testGetAvailableShifts(): void
    {
        // Create unassigned shifts
        $shift1 = new Shift();
        $shift1->setJob($this->testJob);
        $shift1->setSite($this->testSite);
        $shift1->setRobot($this->testRobot);
        $shift1->setStartTimeUtc(new \DateTime('+1 day 09:00'));
        $shift1->setEndTimeUtc(new \DateTime('+1 day 13:00'));
        $shift1->setStatus('unassigned');
        $this->entityManager->persist($shift1);

        $shift2 = new Shift();
        $shift2->setJob($this->testJob);
        $shift2->setSite($this->testSite);
        $shift2->setRobot($this->testRobot);
        $shift2->setStartTimeUtc(new \DateTime('+2 days 09:00'));
        $shift2->setEndTimeUtc(new \DateTime('+2 days 13:00'));
        $shift2->setStatus('unassigned');
        $this->entityManager->persist($shift2);

        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('GET', '/api/shifts/available');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('shifts', $response);
        $this->assertGreaterThanOrEqual(2, count($response['shifts']));
    }

    public function testAcceptShift(): void
    {
        // Create an unassigned shift
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('+3 days 10:00'));
        $shift->setEndTimeUtc(new \DateTime('+3 days 14:00'));
        $shift->setStatus('unassigned');
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/shifts/' . $shift->getId() . '/accept');

        $this->assertResponseIsSuccessful();

        // Verify assignment
        $this->entityManager->refresh($shift);
        $this->assertEquals('assigned', $shift->getStatus());
        $this->assertEquals($this->testOperator->getId(), $shift->getOperator()->getId());
    }

    public function testAcceptAlreadyAssignedShift(): void
    {
        // Create another operator
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $otherOperator = new Operator();
        $otherOperator->setEmail('other' . uniqid() . '@test.com');
        $otherOperator->setFirstName('Other');
        $otherOperator->setLastName('Operator');
        $otherOperator->setStatus('active');
        $otherOperator->setPasswordHash($passwordHasher->hashPassword($otherOperator, 'Test123!'));
        $this->entityManager->persist($otherOperator);

        // Create an already assigned shift
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('+4 days 10:00'));
        $shift->setEndTimeUtc(new \DateTime('+4 days 14:00'));
        $shift->setStatus('assigned');
        $shift->setOperator($otherOperator);
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/shifts/' . $shift->getId() . '/accept');

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testCancelShift(): void
    {
        // Create an assigned shift
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('+5 days 10:00'));
        $shift->setEndTimeUtc(new \DateTime('+5 days 14:00'));
        $shift->setStatus('assigned');
        $shift->setOperator($this->testOperator);
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'POST',
            '/api/shifts/' . $shift->getId() . '/cancel',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['reason' => 'Personal emergency'])
        );

        $this->assertResponseIsSuccessful();

        // Verify cancellation
        $this->entityManager->refresh($shift);
        $this->assertEquals('cancelled', $shift->getStatus());
    }

    public function testCancelOtherOperatorShift(): void
    {
        // Create another operator
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $otherOperator = new Operator();
        $otherOperator->setEmail('assigned' . uniqid() . '@test.com');
        $otherOperator->setFirstName('Assigned');
        $otherOperator->setLastName('Operator');
        $otherOperator->setStatus('active');
        $otherOperator->setPasswordHash($passwordHasher->hashPassword($otherOperator, 'Test123!'));
        $this->entityManager->persist($otherOperator);

        // Create shift assigned to other operator
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('+6 days 10:00'));
        $shift->setEndTimeUtc(new \DateTime('+6 days 14:00'));
        $shift->setStatus('assigned');
        $shift->setOperator($otherOperator);
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'POST',
            '/api/shifts/' . $shift->getId() . '/cancel',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['reason' => 'Testing'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testStartSession(): void
    {
        // Create an assigned shift
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('-1 hour'));
        $shift->setEndTimeUtc(new \DateTime('+3 hours'));
        $shift->setStatus('assigned');
        $shift->setOperator($this->testOperator);
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/shifts/' . $shift->getId() . '/session/start');

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('session', $response);
        $this->assertEquals('active', $response['session']['status']);

        // Verify shift status changed
        $this->entityManager->refresh($shift);
        $this->assertEquals('in_progress', $shift->getStatus());
    }

    public function testStartSessionOnUnassignedShift(): void
    {
        // Create an unassigned shift
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('-1 hour'));
        $shift->setEndTimeUtc(new \DateTime('+3 hours'));
        $shift->setStatus('unassigned');
        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/shifts/' . $shift->getId() . '/session/start');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testEndSession(): void
    {
        // Create a shift with active session
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('-2 hours'));
        $shift->setEndTimeUtc(new \DateTime('+2 hours'));
        $shift->setStatus('in_progress');
        $shift->setOperator($this->testOperator);
        $this->entityManager->persist($shift);

        $session = new Session();
        $session->setShift($shift);
        $session->setOperator($this->testOperator);
        $session->setRobot($this->testRobot);
        $session->setStartedAt(new \DateTime('-1 hour'));
        $session->setStatus('active');
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request(
            'POST',
            '/api/shifts/' . $shift->getId() . '/session/' . $session->getId() . '/end',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['reason' => 'shift_completed'])
        );

        $this->assertResponseIsSuccessful();

        // Verify session ended
        $this->entityManager->refresh($session);
        $this->assertEquals('completed', $session->getStatus());
        $this->assertNotNull($session->getEndedAt());

        // Verify shift completed
        $this->entityManager->refresh($shift);
        $this->assertEquals('completed', $shift->getStatus());
    }

    public function testSessionHeartbeat(): void
    {
        // Create a shift with active session
        $shift = new Shift();
        $shift->setJob($this->testJob);
        $shift->setSite($this->testSite);
        $shift->setRobot($this->testRobot);
        $shift->setStartTimeUtc(new \DateTime('-1 hour'));
        $shift->setEndTimeUtc(new \DateTime('+3 hours'));
        $shift->setStatus('in_progress');
        $shift->setOperator($this->testOperator);
        $this->entityManager->persist($shift);

        $session = new Session();
        $session->setShift($shift);
        $session->setOperator($this->testOperator);
        $session->setRobot($this->testRobot);
        $session->setStartedAt(new \DateTime('-30 minutes'));
        $session->setStatus('active');
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/sessions/' . $session->getId() . '/heartbeat');

        $this->assertResponseIsSuccessful();

        // Verify heartbeat updated
        $this->entityManager->refresh($session);
        $this->assertNotNull($session->getLastHeartbeatAt());
    }

    public function testDetectShiftConflict(): void
    {
        // Create an assigned shift
        $existingShift = new Shift();
        $existingShift->setJob($this->testJob);
        $existingShift->setSite($this->testSite);
        $existingShift->setRobot($this->testRobot);
        $existingShift->setStartTimeUtc(new \DateTime('+7 days 09:00'));
        $existingShift->setEndTimeUtc(new \DateTime('+7 days 17:00'));
        $existingShift->setStatus('assigned');
        $existingShift->setOperator($this->testOperator);
        $this->entityManager->persist($existingShift);

        // Create overlapping shift
        $newShift = new Shift();
        $newShift->setJob($this->testJob);
        $newShift->setSite($this->testSite);
        $newShift->setRobot($this->testRobot);
        $newShift->setStartTimeUtc(new \DateTime('+7 days 12:00'));
        $newShift->setEndTimeUtc(new \DateTime('+7 days 20:00'));
        $newShift->setStatus('unassigned');
        $this->entityManager->persist($newShift);
        $this->entityManager->flush();

        $this->client->loginUser($this->testOperator);

        $this->client->request('POST', '/api/shifts/' . $newShift->getId() . '/accept');

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('conflict', strtolower($response['error']));
    }
}
