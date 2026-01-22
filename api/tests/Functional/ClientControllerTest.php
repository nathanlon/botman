<?php

namespace App\Tests\Functional;

use App\Entity\ClientOrganization;
use App\Entity\ClientUser;
use App\Entity\Robot;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private ?ClientOrganization $testOrganization = null;
    private ?ClientUser $testClientUser = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createTestClient();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createTestClient(): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create organization
        $this->testOrganization = new ClientOrganization();
        $this->testOrganization->setCompanyName('Test Corp ' . uniqid());
        $this->testOrganization->setStatus('active');
        $this->entityManager->persist($this->testOrganization);

        // Create client user
        $this->testClientUser = new ClientUser();
        $this->testClientUser->setEmail('client' . uniqid() . '@test.com');
        $this->testClientUser->setFirstName('Test');
        $this->testClientUser->setLastName('Client');
        $this->testClientUser->setOrganization($this->testOrganization);
        $this->testClientUser->setRole('admin');

        $hashedPassword = $passwordHasher->hashPassword($this->testClientUser, 'TestPass123!');
        $this->testClientUser->setPasswordHash($hashedPassword);

        $this->entityManager->persist($this->testClientUser);
        $this->entityManager->flush();
    }

    public function testGetOrganizationProfile(): void
    {
        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/client/organization');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('organization', $response);
        $this->assertEquals($this->testOrganization->getCompanyName(), $response['organization']['companyName']);
    }

    public function testUpdateOrganization(): void
    {
        $this->client->loginUser($this->testClientUser);

        $this->client->request(
            'PUT',
            '/api/client/organization',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'companyName' => 'Updated Corp Name',
                'billingEmail' => 'billing@test.com',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Organization updated', $response['message']);

        // Verify in database
        $this->entityManager->refresh($this->testOrganization);
        $this->assertEquals('Updated Corp Name', $this->testOrganization->getCompanyName());
    }

    public function testCreateSite(): void
    {
        $this->client->loginUser($this->testClientUser);

        $this->client->request(
            'POST',
            '/api/client/sites',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Warehouse A',
                'address' => '123 Industrial Park',
                'regionCode' => 'US-CA',
                'timezoneId' => 'America/Los_Angeles',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('site', $response);
        $this->assertEquals('Warehouse A', $response['site']['name']);
    }

    public function testGetSites(): void
    {
        // Create a site first
        $site = new Site();
        $site->setOrganization($this->testOrganization);
        $site->setName('Test Site');
        $site->setRegionCode('US-NY');
        $site->setStatus('active');
        $this->entityManager->persist($site);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/client/sites');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('sites', $response);
        $this->assertGreaterThanOrEqual(1, count($response['sites']));
    }

    public function testGetSiteWithRobots(): void
    {
        // Create a site
        $site = new Site();
        $site->setOrganization($this->testOrganization);
        $site->setName('Robot Site');
        $site->setRegionCode('US-TX');
        $site->setStatus('active');
        $this->entityManager->persist($site);

        // Create robots
        $robot1 = new Robot();
        $robot1->setSite($site);
        $robot1->setName('Robot-001');
        $robot1->setModel('Universal-UR10');
        $robot1->setStatus('online');
        $this->entityManager->persist($robot1);

        $robot2 = new Robot();
        $robot2->setSite($site);
        $robot2->setName('Robot-002');
        $robot2->setModel('Franka-Emika');
        $robot2->setStatus('offline');
        $this->entityManager->persist($robot2);

        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('GET', '/api/client/sites/' . $site->getId());

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('site', $response);
        $this->assertArrayHasKey('robots', $response['site']);
        $this->assertCount(2, $response['site']['robots']);
    }

    public function testCreateRobot(): void
    {
        // Create a site first
        $site = new Site();
        $site->setOrganization($this->testOrganization);
        $site->setName('Robot Factory');
        $site->setRegionCode('US-WA');
        $site->setStatus('active');
        $this->entityManager->persist($site);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request(
            'POST',
            '/api/client/sites/' . $site->getId() . '/robots',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Robot',
                'model' => 'OpenArm-7DOF',
                'connectionEndpoint' => 'wss://robot.example.com/connect',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('robot', $response);
        $this->assertEquals('New Robot', $response['robot']['name']);
        $this->assertEquals('offline', $response['robot']['status']);
    }

    public function testCreateRobotOnOtherOrganizationSite(): void
    {
        // Create another organization's site
        $otherOrg = new ClientOrganization();
        $otherOrg->setCompanyName('Other Corp');
        $otherOrg->setStatus('active');
        $this->entityManager->persist($otherOrg);

        $otherSite = new Site();
        $otherSite->setOrganization($otherOrg);
        $otherSite->setName('Other Site');
        $otherSite->setRegionCode('US-FL');
        $otherSite->setStatus('active');
        $this->entityManager->persist($otherSite);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request(
            'POST',
            '/api/client/sites/' . $otherSite->getId() . '/robots',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Sneaky Robot',
                'model' => 'OpenArm-7DOF',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateSite(): void
    {
        // Create a site
        $site = new Site();
        $site->setOrganization($this->testOrganization);
        $site->setName('Original Name');
        $site->setRegionCode('US-CA');
        $site->setStatus('active');
        $this->entityManager->persist($site);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request(
            'PUT',
            '/api/client/sites/' . $site->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'address' => 'New Address',
            ])
        );

        $this->assertResponseIsSuccessful();

        // Verify in database
        $this->entityManager->refresh($site);
        $this->assertEquals('Updated Name', $site->getName());
        $this->assertEquals('New Address', $site->getAddress());
    }

    public function testDeactivateSite(): void
    {
        // Create a site
        $site = new Site();
        $site->setOrganization($this->testOrganization);
        $site->setName('To Deactivate');
        $site->setRegionCode('US-GA');
        $site->setStatus('active');
        $this->entityManager->persist($site);
        $this->entityManager->flush();

        $this->client->loginUser($this->testClientUser);

        $this->client->request('DELETE', '/api/client/sites/' . $site->getId());

        $this->assertResponseIsSuccessful();

        // Verify deactivation
        $this->entityManager->refresh($site);
        $this->assertEquals('inactive', $site->getStatus());
    }
}
