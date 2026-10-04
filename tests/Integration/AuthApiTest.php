<?php

namespace Tests\Integration;

use App\Entities\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Support\ApiTestCase;

final class AuthApiTest extends ApiTestCase
{
    public function testLoginReturnsABearerToken(): void
    {
        $this->createUser();

        $response = $this->request('POST', '/auth/login', ['email' => 'ADA@example.com ', 'password' => 'correct horse']);
        $body = $this->json($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bearer', $body['token_type']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $body['token']);
        $this->assertGreaterThan(new \DateTimeImmutable(), new \DateTimeImmutable($body['expires_at']));
    }

    public function testTokensAreStoredHashed(): void
    {
        $this->createUser();
        $token = $this->login();

        $stored = $this->app->getContainer()->get(EntityManagerInterface::class)
            ->getConnection()->fetchOne('SELECT token_hash FROM api_tokens');

        $this->assertSame(hash('sha256', $token), $stored);
    }

    public function testWrongPasswordAndUnknownEmailGiveTheSameResponse(): void
    {
        $this->createUser();

        $wrongPassword = $this->request('POST', '/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong password']);
        $unknownEmail = $this->request('POST', '/auth/login', ['email' => 'nobody@example.com', 'password' => 'correct horse']);

        $this->assertSame(401, $wrongPassword->getStatusCode());
        $this->assertSame(401, $unknownEmail->getStatusCode());
        $this->assertSame((string) $wrongPassword->getBody(), (string) $unknownEmail->getBody());
    }

    public function testLoginValidatesItsInput(): void
    {
        $response = $this->request('POST', '/auth/login', ['email' => '']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('password', $this->json($response)['error']['errors']);
    }

    public function testMeReturnsTheAuthenticatedUser(): void
    {
        $user = $this->createUser();

        $response = $this->request('GET', '/auth/me', token: $this->login());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user, $this->json($response));
    }

    public function testProtectedRoutesRequireAToken(): void
    {
        $response = $this->request('GET', '/auth/me');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->getHeaderLine('WWW-Authenticate'));
    }

    public function testUnknownTokenIsRejected(): void
    {
        $this->assertSame(401, $this->request('GET', '/auth/me', token: str_repeat('a', 64))->getStatusCode());
    }

    public function testLogoutRevokesTheToken(): void
    {
        $this->createUser();
        $token = $this->login();

        $this->assertSame(204, $this->request('POST', '/auth/logout', token: $token)->getStatusCode());
        $this->assertSame(401, $this->request('GET', '/auth/me', token: $token)->getStatusCode());
    }

    public function testExpiredTokensAreRejectedAndDeleted(): void
    {
        $this->createUser();
        $token = $this->login();

        $entityManager = $this->app->getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(
            'UPDATE api_tokens SET expires_at = ?',
            [(new \DateTimeImmutable('-1 second'))->format('Y-m-d H:i:s')],
        );
        $entityManager->clear();

        $this->assertSame(401, $this->request('GET', '/auth/me', token: $token)->getStatusCode());
        $this->assertSame(0, $entityManager->getRepository(ApiToken::class)->count());
    }
}
