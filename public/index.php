<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$appEnv   = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development';
$debugEnv = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? null;
$debug    = $debugEnv !== null
    ? in_array(strtolower((string) $debugEnv), ['1', 'true', 'yes', 'on'], true)
    : ($appEnv !== 'production');

ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');

try {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__ . '/../config/container.php');
    $container = $builder->build();

    $app = AppFactory::createFromContainer($container);
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware($debug, true, true);

    (require __DIR__ . '/../config/routes.php')($app);

    $app->run();
} catch (\Throwable $e) {
    if ($debug) {
        header('Content-Type: text/plain');
        echo 'Application error: ' . $e->getMessage();
    }
}


