<?php

use App\Handlers\JsonErrorHandler;
use App\Middleware\CorsMiddleware;
use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

return (static function (): App {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__ . '/container.php');
    $container = $builder->build();
    $settings  = $container->get('settings');

    $app = AppFactory::createFromContainer($container);

    // Middleware added last runs first
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware($settings['debug'], true, true);
    $errorMiddleware->setDefaultErrorHandler(
        new JsonErrorHandler($app->getCallableResolver(), $app->getResponseFactory())
    );

    $app->add(new CorsMiddleware($settings['cors'], $app->getResponseFactory()));

    (require __DIR__ . '/routes.php')($app);

    return $app;
})();
