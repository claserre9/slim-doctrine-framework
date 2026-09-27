<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

class HttpTest extends TestCase
{
    /** @var array<string, string> */
    private array $env = [];

    protected function tearDown(): void
    {
        foreach (array_keys($this->env) as $key) {
            unset($_ENV[$key]);
        }
    }

    /**
     * @param array<string, string> $env
     *
     * @return App<ContainerInterface>
     */
    private function createApp(array $env = []): App
    {
        $this->env = $env;
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }

        return require __DIR__ . '/../config/bootstrap.php';
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $env
     */
    private function request(string $method, string $uri, array $headers = [], array $env = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->createApp($env)->handle($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(ResponseInterface $response): array
    {
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function endpoints(): array
    {
        return [
            'root' => ['/', ['status' => 'ok']],
            'health' => ['/health', ['status' => 'ok']],
            'api' => ['/api', ['message' => 'Hello World']],
            'api with name' => ['/api?name=Ada', ['message' => 'Hello Ada']],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('endpoints')]
    public function testEndpointReturnsJson(string $uri, array $expected): void
    {
        $response = $this->request('GET', $uri);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, $this->json($response));
    }

    public function testInvalidInputReturns422WithErrorsEvenInProduction(): void
    {
        $response = $this->request('GET', '/api?name=A', env: ['APP_ENV' => 'production', 'APP_DEBUG' => '0']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'error' => [
                'code' => 422,
                'message' => '422 Unprocessable Entity',
                'errors' => ['name' => ['Name must be at least 2 characters long']],
            ],
        ], $this->json($response));
    }

    public function testUnknownRouteReturnsJson404WithoutDetailsInProduction(): void
    {
        $response = $this->request('GET', '/does-not-exist', env: ['APP_ENV' => 'production', 'APP_DEBUG' => '0']);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => ['code' => 404, 'message' => '404 Not Found']], $this->json($response));
    }

    public function testErrorDetailsAreShownInDebug(): void
    {
        $response = $this->request('GET', '/does-not-exist', env: ['APP_DEBUG' => '1']);

        $this->assertArrayHasKey('details', $this->json($response)['error']);
    }

    public function testWrongMethodReturns405WithAllowHeader(): void
    {
        $response = $this->request('POST', '/health');

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET', $response->getHeaderLine('Allow'));
    }

    public function testCorsPreflightIsAnsweredBeforeRouting(): void
    {
        $response = $this->request('OPTIONS', '/api', [
            'Origin' => 'https://app.example',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotSame('', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    public function testCorsHeadersAreAddedToErrorResponses(): void
    {
        $response = $this->request('GET', '/does-not-exist', ['Origin' => 'https://app.example']);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testCorsAllowListRejectsUnknownOrigins(): void
    {
        $env = ['CORS_ORIGINS' => 'https://allowed.example'];

        $allowed = $this->request('GET', '/health', ['Origin' => 'https://allowed.example'], $env);
        $this->assertSame('https://allowed.example', $allowed->getHeaderLine('Access-Control-Allow-Origin'));

        $denied = $this->request('GET', '/health', ['Origin' => 'https://evil.example'], $env);
        $this->assertFalse($denied->hasHeader('Access-Control-Allow-Origin'));
    }
}
