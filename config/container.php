<?php

use App\Config\DoctrineFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

return [
    Configuration::class => DI\factory([DoctrineFactory::class, 'createConfiguration']),
    Connection::class    => DI\factory([DoctrineFactory::class, 'createConnection']),
    EntityManager::class => DI\factory([DoctrineFactory::class, 'createEntityManager']),
];
