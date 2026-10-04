<?php

use App\Config\DoctrineFactory;
use App\Services\AuthService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return [
    'settings' => require __DIR__ . '/settings.php',

    DoctrineFactory::class => static fn (ContainerInterface $c) => new DoctrineFactory($c->get('settings')['doctrine']),

    Configuration::class          => static fn (DoctrineFactory $f) => $f->createConfiguration(),
    Connection::class             => static fn (DoctrineFactory $f, Configuration $config) => $f->createConnection($config),
    EntityManagerInterface::class => static fn (DoctrineFactory $f, Connection $connection, Configuration $config)
        => $f->createEntityManager($connection, $config),

    AuthService::class => DI\autowire()
        ->constructorParameter('tokenTtl', DI\factory(static fn (ContainerInterface $c) => $c->get('settings')['auth']['token_ttl'])),

    ValidatorInterface::class => static fn () => Validation::createValidatorBuilder()
        ->enableAttributeMapping()
        ->getValidator(),
];
