<?php

namespace Tests\Support;

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

final class Database
{
    public static function dependencyFactory(ContainerInterface $container): DependencyFactory
    {
        return DependencyFactory::fromEntityManager(
            new ConfigurationArray($container->get('settings')['migrations']),
            new ExistingEntityManager($container->get(EntityManagerInterface::class)),
        );
    }

    /**
     * Applies the real migrations (the same ones as in production) up to $version.
     */
    public static function migrate(ContainerInterface $container, string $version = 'latest'): void
    {
        $factory = self::dependencyFactory($container);
        $factory->getMetadataStorage()->ensureInitialized();

        $plan = $factory->getMigrationPlanCalculator()->getPlanUntilVersion(
            $factory->getVersionAliasResolver()->resolveVersionAlias($version),
        );

        $factory->getMigrator()->migrate($plan, new MigratorConfiguration());
    }
}
