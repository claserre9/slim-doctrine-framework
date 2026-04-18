<?php

use App\Controllers\ApiController;
use Slim\App;
use Slim\Psr7\Response;

return function (App $app): void {
    $app->get('/api', [ApiController::class, 'index']);

    $app->get('/health', function ($request, Response $response): Response {
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
