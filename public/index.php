<?php

use App\Config\Environment;

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require __DIR__ . '/../config/bootstrap.php';

    $debug = $app->getContainer()->get('settings')['debug'];
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');

    $app->run();
} catch (\Throwable $e) {
    error_log((string) $e);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }

    echo json_encode(
        ['error' => Environment::isDebug() ? $e->getMessage() : 'Internal Server Error'],
        JSON_THROW_ON_ERROR
    );
}
