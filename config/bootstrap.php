<?php

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

    $app = AppFactory::createFromContainer($container);
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware($container->get('settings')['debug'], true, true);

    (require __DIR__ . '/routes.php')($app);

    return $app;
})();
