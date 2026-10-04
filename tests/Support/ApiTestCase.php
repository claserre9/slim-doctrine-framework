<?php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Boots the application on a fresh in-memory database with all migrations applied.
 */
abstract class ApiTestCase extends TestCase
{
    /** @var App<ContainerInterface> */
    protected App $app;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../config/bootstrap.php';
        Database::migrate($this->app->getContainer());
    }

    /**
     * @param array<string, mixed>|null $body sent as JSON
     */
    protected function request(string $method, string $uri, ?array $body = null, ?string $token = null): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
        }

        if ($token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }

        return $this->app->handle($request);
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed> the created user
     */
    protected function createUser(string $email = 'ada@example.com', string $name = 'Ada Lovelace', string $password = 'correct horse'): array
    {
        $response = $this->request('POST', '/users', ['email' => $email, 'name' => $name, 'password' => $password]);
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response);
    }

    protected function login(string $email = 'ada@example.com', string $password = 'correct horse'): string
    {
        $response = $this->request('POST', '/auth/login', ['email' => $email, 'password' => $password]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response)['token'];
    }
}
