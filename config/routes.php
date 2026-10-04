<?php

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/', HealthController::class);
    $app->get('/health', HealthController::class);

    $app->get('/api', [ApiController::class, 'index']);

    // Public
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->post('/users', [UserController::class, 'create']);

    // Requires `Authorization: Bearer <token>`
    $app->group('', function (RouteCollectorProxy $group): void {
        $group->post('/auth/logout', [AuthController::class, 'logout']);
        $group->get('/auth/me', [AuthController::class, 'me']);

        $group->get('/users', [UserController::class, 'list']);
        $group->get('/users/{id:[0-9]+}', [UserController::class, 'show']);
        $group->patch('/users/{id:[0-9]+}', [UserController::class, 'update']);
        $group->delete('/users/{id:[0-9]+}', [UserController::class, 'delete']);
    })->add(AuthMiddleware::class);
};
