<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegisterOperatorSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'operator' . uniqid() . '@test.com',
                'password' => 'SecurePass123!',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'userType' => 'operator',
                'regionCode' => 'US-CA',
                'timezoneId' => 'America/Los_Angeles',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('userId', $response);
        $this->assertEquals('Registration successful', $response['message']);
    }

    public function testRegisterClientSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'client' . uniqid() . '@test.com',
                'password' => 'SecurePass123!',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'userType' => 'client',
                'companyName' => 'Acme Corp',
                'regionCode' => 'US-NY',
                'timezoneId' => 'America/New_York',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('userId', $response);
        $this->assertArrayHasKey('organizationId', $response);
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'invalid-email',
                'password' => 'SecurePass123!',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'userType' => 'operator',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterWithMissingFields(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@test.com',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $email = 'duplicate' . uniqid() . '@test.com';

        // First registration
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'SecurePass123!',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'userType' => 'operator',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Duplicate registration
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'SecurePass123!',
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'userType' => 'operator',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testRegisterClientWithoutCompanyName(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'client' . uniqid() . '@test.com',
                'password' => 'SecurePass123!',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'userType' => 'client',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
