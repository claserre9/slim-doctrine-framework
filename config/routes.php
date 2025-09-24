<?php

use App\controllers\ApiController;
use App\middleware\ValidationMiddleware;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Psr7\Response;

return static function (App $app, ContainerInterface $container): void {
    // Health check endpoint
    $app->get('/health', function ($request, Response $response) {
        $payload = json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Example API route with simple validation example (optional query param `name`)
    $route = $app->get('/api', [ApiController::class, 'index']);
    $route->add(new ValidationMiddleware([
        // 'name' is optional but if present must be a string of min 2
        'name' => ['string', 'min:2']
    ]));
};
