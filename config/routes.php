<?php

use App\Controllers\ApiController;
use App\Controllers\HealthController;
use App\Middleware\ValidationMiddleware;
use Slim\App;

return function (App $app): void {
    $app->get('/', HealthController::class);
    $app->get('/health', HealthController::class);

    // Example of per-route validation: `name` is optional, but must be a string of at least 2 chars
    $app->get('/api', [ApiController::class, 'index'])
        ->add(new ValidationMiddleware(['name' => ['string', 'min:2']], $app->getResponseFactory()));
};
