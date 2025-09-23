<?php

use App\controllers\ApiController;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use Slim\Psr7\Response;

require __DIR__.'/../vendor/autoload.php';
session_start();

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

// Determine environment and debug mode
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development';
$debugEnv = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? null;
$debug = $debugEnv !== null
    ? in_array(strtolower((string)$debugEnv), ['1','true','yes','on'], true)
    : ($appEnv !== 'production');

// Configure PHP error display based on environment
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');

try {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__.'/../config/container.php');
    $container = $builder->build();
    $app = AppFactory::createFromContainer($container);
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // Slim error middleware: displayErrorDetails controlled by $debug
    $app->addErrorMiddleware($debug, true, true);

    // Routes
    $app->get('/api', [ApiController::class, 'index']);

    // Health check endpoint
    $app->get('/health', function ($request, Response $response) {
        $payload = json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->run();
} catch (\Throwable $e) {
    // In production, avoid leaking errors; in debug, you may log/echo as needed.
    if ($debug) {
        header('Content-Type: text/plain');
        echo 'Application error: ' . $e->getMessage();
    }
}

