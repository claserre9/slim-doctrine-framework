<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

class HttpTest extends TestCase
{
    /**
     * @return App<ContainerInterface>
     */
    private function createApp(): App
    {
        return require __DIR__ . '/../config/bootstrap.php';
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function endpoints(): array
    {
        return [
            'root'   => ['/', ['status' => 'ok']],
            'health' => ['/health', ['status' => 'ok']],
            'api'    => ['/api', ['message' => 'Hello World']],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('endpoints')]
    public function testEndpointReturnsJson(string $path, array $expected): void
    {
        $request  = (new ServerRequestFactory())->createServerRequest('GET', $path);
        $response = $this->createApp()->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame($expected, json_decode((string) $response->getBody(), true));
    }

    public function testUnknownRouteReturns404(): void
    {
        $request  = (new ServerRequestFactory())->createServerRequest('GET', '/does-not-exist');
        $response = $this->createApp()->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
