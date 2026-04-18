<?php

namespace App\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class DoctrineFactory
{
    public static function createEntityManager(Configuration $config, Connection $connection): EntityManager
    {
        return new EntityManager($connection, $config);
    }

    public static function createConfiguration(): Configuration
    {
        $config = new Configuration();

        $config->setMetadataCache(self::createCache('metadata'));
        $config->setQueryCache(self::createCache('queries'));

        $driverImpl = new AttributeDriver([__DIR__ . '/../../src/Entities']);
        $config->setMetadataDriverImpl($driverImpl);

        $config->setProxyDir(__DIR__ . '/../../var/cache/doctrine/proxies');
        $config->setProxyNamespace('App\Proxies');
        $config->setAutoGenerateProxyClasses(self::isDevelopment());

        return $config;
    }

    public static function createConnection(): Connection
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $params = ['driver' => $driver];

        switch ($driver) {
            case 'pdo_sqlite':
                $path = $_ENV['DB_PATH'] ?? __DIR__ . '/../../var/data/database.sqlite';
                self::ensureDirectoryExists(dirname($path));
                $params['path'] = $path;
                break;

            case 'pdo_mysql':
                $params += [
                    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
                    'port'     => $_ENV['DB_PORT'] ?? 3306,
                    'dbname'   => $_ENV['DB_NAME'],
                    'user'     => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'charset'  => 'utf8mb4',
                ];
                break;

            case 'pdo_pgsql':
                $params += [
                    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
                    'port'     => $_ENV['DB_PORT'] ?? 5432,
                    'dbname'   => $_ENV['DB_NAME'],
                    'user'     => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'charset'  => 'utf8',
                ];
                break;

            default:
                throw new \InvalidArgumentException("Unsupported database driver: $driver");
        }

        return DriverManager::getConnection($params);
    }

    private static function createCache(string $type): ArrayAdapter|PhpFilesAdapter
    {
        return self::isDevelopment()
            ? new ArrayAdapter()
            : new PhpFilesAdapter("doctrine_$type");
    }

    private static function isDevelopment(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'development';
    }

    private static function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}