<?php

use App\Controllers\ApiController;
use App\Controllers\HealthController;
use Slim\App;

return function (App $app): void {
    $app->get('/', HealthController::class);
    $app->get('/health', HealthController::class);

    $app->get('/api', [ApiController::class, 'index']);
};
