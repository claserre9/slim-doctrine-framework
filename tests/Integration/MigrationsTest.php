<?php

namespace Tests\Integration;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Tests\Support\Database;

final class MigrationsTest extends TestCase
{
    /**
     * @return array{ContainerInterface, EntityManagerInterface}
     */
    private function boot(): array
    {
        /** @var App<ContainerInterface> $app */
        $app = require __DIR__ . '/../../config/bootstrap.php';
        $container = $app->getContainer();

        return [$container, $container->get(EntityManagerInterface::class)];
    }

    public function testMigrationsMatchTheEntityMapping(): void
    {
        [$container, $entityManager] = $this->boot();
        Database::migrate($container);

        // The migrations table is not an entity
        $migrationsTable = $container->get('settings')['migrations']['table_storage']['table_name'];
        $entityManager->getConnection()->getConfiguration()->setSchemaAssetsFilter(
            static fn (string|AbstractAsset $asset): bool => ($asset instanceof AbstractAsset ? $asset->getName() : $asset) !== $migrationsTable,
        );

        $diff = (new SchemaTool($entityManager))->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());

        $this->assertSame([], $diff, 'The migrations do not produce the schema described by the entities: add a migration.');
    }

    public function testMigrationsCanBeRolledBack(): void
    {
        [$container, $entityManager] = $this->boot();
        Database::migrate($container);
        Database::migrate($container, 'first');

        $tables = $entityManager->getConnection()->createSchemaManager()->listTableNames();

        $this->assertSame(['doctrine_migration_versions'], $tables);
    }
}
