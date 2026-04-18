<?php

namespace tests;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Dotenv\Dotenv;

class HealthTest extends TestCase
{
    private function createApp(): \Slim\App
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            (new Dotenv())->load($envFile);
        }

        $builder = new ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../config/container.php');
        $container = $builder->build();

        $app = AppFactory::createFromContainer($container);
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(false, false, false);

        (require __DIR__ . '/../config/routes.php')($app);

        return $app;
    }

    public function testHealthEndpointReturnsOk(): void
    {
        $app     = $this->createApp();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('ok', $body['status']);
    }
}
