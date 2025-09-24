<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use App\middleware\CorsMiddleware;
use App\middleware\ErrorHandler;

require __DIR__.'/../vendor/autoload.php';
session_start();

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

try {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__.'/../config/container.php');
    $container = $builder->build();

    $settings = $container->get('settings');
    $debug = (bool)($settings['displayErrorDetails'] ?? false);

    // Configure PHP error display based on environment
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');

    $app = AppFactory::createFromContainer($container);

    // Global middlewares
    $app->addBodyParsingMiddleware();
    $app->add(new CorsMiddleware($settings));
    $app->addRoutingMiddleware();

    // Slim error middleware: displayErrorDetails controlled by settings
    $errorMiddleware = $app->addErrorMiddleware($debug, true, true);
    $callableResolver = $app->getCallableResolver();
    $responseFactory = $app->getResponseFactory();
    $errorMiddleware->setDefaultErrorHandler(new ErrorHandler($callableResolver, $responseFactory));

    // Routes from a centralized config file
    $registerRoutes = require __DIR__ . '/../config/routes.php';
    $registerRoutes($app, $container);

    $app->run();
} catch (\Throwable $e) {
    $isDebug = isset($debug) ? $debug : false;
    if ($isDebug) {
        header('Content-Type: text/plain');
        echo 'Application error: ' . $e->getMessage();
    }
}

