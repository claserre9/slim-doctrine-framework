<?php

use App\controllers\ApiController;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__.'/../vendor/autoload.php';
session_start();


$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

try {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__.'/../config/container.php');
    $container = $builder->build();
    $app = AppFactory::createFromContainer($container);
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $app->addErrorMiddleware(true, true, true);

    $app->get('/api', [ApiController::class, 'index']);


    $app->run();
} catch (Exception $e) {
}

