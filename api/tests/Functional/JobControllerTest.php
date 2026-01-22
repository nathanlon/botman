<?php

namespace App\Tests\Functional;

use App\Entity\ClientOrganization;
use App\Entity\ClientUser;
use App\Entity\Job;
use App\Entity\Robot;
use App\Entity\Shift;
use App\Entity\Site;
use App\Entity\SkillDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class JobControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private ?ClientOrganization $testOrganization = null;
    private ?ClientUser $testClientUser = null;
    private ?Site $testSite = null;
    private ?Robot $testRobot = null;

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
        $this->testOrganization->setCompanyName('Job Test Corp ' . uniqid());
        $this->testOrganization->setStatus('active');
        $this->entityManager->persist($this->testOrganization);

        // Create site
        $this->testSite = new Site();
        $this->testSite->setOrganization($this->testOrganization);
        $this->testSite->setName('Job Test Site');
        $this->testSite->setRegionCode('US-CA');
        $this->testSite->setStatus('active');
        $this->testSite->setTimezoneId('America/Los_Angeles');
        $this->entityManager->persist($this->testSite);

        // Create robot
        $this->testRobot = new Robot();
        $this->testRobot->setSite($this->testSite);
        $this->testRobot->setName('Test Robot');
        $this->testRobot->setModel('Universal-UR10');
        $this->testRobot->setStatus('online');
        $this->entityManager->persist($this->testRobot);

        // Create client user
        $this->testClientUser = new ClientUser();
        $this->testClientUser->setEmail('jobclient' . uniqid() . '@test.com');
        $this->testClientUser->setFirstName('Job');
        $this->testClientUser->setLastName('Manager');
        $this->testClientUser->setOrganization($this->testOrganization);
        $this->testClientUser->setRole('admin');

        $hashedPassword = $passwordHasher->hashPassword($this->testClientUser, 'TestPass123!');
        $this->testClientUser->setPasswordHash($hashedPassword);

        $this->entityManager->persist($this->testClientUser);
        $this->entityManager->flush();
    }

    public function testCreateJob(): void
    {
        $this->client->loginUser($this->testClientUser);

        $startDate = (new \DateTime('+1 day'))->format('Y-m-d');
        $endDate = (new \DateTime('+7 days'))->format('Y-m-d');

        $this->client->request(
            'POST',
            '/api/client/jobs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Robot Operator Position',
                'description' => 'Looking for experienced robot operators',
                'siteId' => $this->testSite->getId(),
                'robotId' => $this->testRobot->getId(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'hourlyRateAmount' => 25.00,
                'hourlyRateCurrency' => 'USD',
                'maxLatencyMs' => 150,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('job', $response);
        $this->assertEquals('Robot Operator Position', $response['job']['title']);
        $this->assertEquals('draft', $response['job']['status']);
    }

    public function testCreateJobWithRequiredSkills(): void
    {
        // Create skills
        $skill1 = new SkillDefinition();
        $skill1->setName('Robot Operation');
        $skill1->setCategory('Operations');
        $this->entityManager->persist($skill1);

        $skill2 = new SkillDefinition();
        $skill2->setName('Safety Training');
        $skill2->setCategory('Safety');
        $this->entityManager->persist($skill2);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $startDate = (new \DateTime('+1 day'))->format('Y-m-d');
        $endDate = (new \DateTime('+7 days'))->format('Y-m-d');

        $this->client->request(
            'POST',
            '/api/client/jobs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Skilled Operator',
                'siteId' => $this->testSite->getId(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'hourlyRateAmount' => 30.00,
                'hourlyRateCurrency' => 'USD',
                'maxLatencyMs' => 100,
                'requiredSkillIds' => [$skill1->getId(), $skill2->getId()],
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('job', $response);
    }

    public function testGetJobs(): void
    {
        // Create a job
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('Test Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+7 days'));
        $job->setHourlyRateAmount(20.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(200);
        $job->setStatus('active');
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/client/jobs');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('jobs', $response);
        $this->assertGreaterThanOrEqual(1, count($response['jobs']));
    }

    public function testGetJobDetail(): void
    {
        // Create a job with shifts
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('Detailed Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+7 days'));
        $job->setHourlyRateAmount(25.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(150);
        $job->setStatus('active');
        $this->entityManager->persist($job);

        // Create shifts
        $shift1 = new Shift();
        $shift1->setJob($job);
        $shift1->setSite($this->testSite);
        $shift1->setStartTimeUtc(new \DateTime('+1 day 09:00'));
        $shift1->setEndTimeUtc(new \DateTime('+1 day 13:00'));
        $shift1->setStatus('unassigned');
        $this->entityManager->persist($shift1);

        $shift2 = new Shift();
        $shift2->setJob($job);
        $shift2->setSite($this->testSite);
        $shift2->setStartTimeUtc(new \DateTime('+1 day 14:00'));
        $shift2->setEndTimeUtc(new \DateTime('+1 day 18:00'));
        $shift2->setStatus('unassigned');
        $this->entityManager->persist($shift2);

        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/client/jobs/' . $job->getId());

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('job', $response);
        $this->assertArrayHasKey('shifts', $response['job']);
        $this->assertCount(2, $response['job']['shifts']);
    }

    public function testActivateJob(): void
    {
        // Create a draft job
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('Draft Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+7 days'));
        $job->setHourlyRateAmount(22.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(200);
        $job->setStatus('draft');
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('POST', '/api/client/jobs/' . $job->getId() . '/activate');

        $this->assertResponseIsSuccessful();

        // Verify status change
        $this->entityManager->refresh($job);
        $this->assertEquals('active', $job->getStatus());
    }

    public function testPauseJob(): void
    {
        // Create an active job
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('Active Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+7 days'));
        $job->setHourlyRateAmount(23.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(180);
        $job->setStatus('active');
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('POST', '/api/client/jobs/' . $job->getId() . '/pause');

        $this->assertResponseIsSuccessful();

        // Verify status change
        $this->entityManager->refresh($job);
        $this->assertEquals('paused', $job->getStatus());
    }

    public function testCancelJob(): void
    {
        // Create a job
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('To Cancel Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+7 days'));
        $job->setHourlyRateAmount(24.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(160);
        $job->setStatus('draft');
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('DELETE', '/api/client/jobs/' . $job->getId());

        $this->assertResponseIsSuccessful();

        // Verify status change
        $this->entityManager->refresh($job);
        $this->assertEquals('cancelled', $job->getStatus());
    }

    public function testAddShiftToJob(): void
    {
        // Create an active job
        $job = new Job();
        $job->setOrganization($this->testOrganization);
        $job->setSite($this->testSite);
        $job->setTitle('Shift Job');
        $job->setStartDate(new \DateTime('+1 day'));
        $job->setEndDate(new \DateTime('+14 days'));
        $job->setHourlyRateAmount(26.00);
        $job->setHourlyRateCurrency('USD');
        $job->setMaxLatencyMs(140);
        $job->setStatus('active');
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $shiftStart = (new \DateTime('+2 days 10:00'))->format('c');
        $shiftEnd = (new \DateTime('+2 days 14:00'))->format('c');

        $this->client->request(
            'POST',
            '/api/client/jobs/' . $job->getId() . '/shifts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'startTimeUtc' => $shiftStart,
                'endTimeUtc' => $shiftEnd,
                'robotId' => $this->testRobot->getId(),
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('shift', $response);
        $this->assertEquals('unassigned', $response['shift']['status']);
    }

    public function testGetMarketplace(): void
    {
        // Create active jobs
        $job1 = new Job();
        $job1->setOrganization($this->testOrganization);
        $job1->setSite($this->testSite);
        $job1->setTitle('Marketplace Job 1');
        $job1->setStartDate(new \DateTime('+1 day'));
        $job1->setEndDate(new \DateTime('+7 days'));
        $job1->setHourlyRateAmount(27.00);
        $job1->setHourlyRateCurrency('USD');
        $job1->setMaxLatencyMs(130);
        $job1->setStatus('active');
        $this->entityManager->persist($job1);

        $job2 = new Job();
        $job2->setOrganization($this->testOrganization);
        $job2->setSite($this->testSite);
        $job2->setTitle('Marketplace Job 2');
        $job2->setStartDate(new \DateTime('+1 day'));
        $job2->setEndDate(new \DateTime('+7 days'));
        $job2->setHourlyRateAmount(28.00);
        $job2->setHourlyRateCurrency('USD');
        $job2->setMaxLatencyMs(120);
        $job2->setStatus('active');
        $this->entityManager->persist($job2);

        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/jobs/marketplace');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('jobs', $response);
        $this->assertGreaterThanOrEqual(2, count($response['jobs']));
    }
}
