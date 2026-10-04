<?php

namespace App\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

final class DoctrineFactory
{
    private const DEFAULT_PORTS = [
        'pdo_mysql' => 3306,
        'pdo_pgsql' => 5432,
    ];

    private const CHARSETS = [
        'pdo_mysql' => 'utf8mb4',
        'pdo_pgsql' => 'utf8',
    ];

    /**
     * @param array{
     *     dev_mode: bool,
     *     entity_dirs: list<string>,
     *     cache_dir: string,
     *     proxy_dir: string,
     *     connection: array<string, mixed>
     * } $settings
     */
    public function __construct(
        private readonly array $settings,
    ) {
    }

    public function createEntityManager(Connection $connection, Configuration $config): EntityManagerInterface
    {
        return new EntityManager($connection, $config);
    }

    public function createConfiguration(): Configuration
    {
        $config = new Configuration();

        $config->setMetadataCache($this->createCache('metadata'));
        $config->setQueryCache($this->createCache('queries'));

        $config->setMetadataDriverImpl(new AttributeDriver($this->settings['entity_dirs']));

        $config->setProxyDir($this->settings['proxy_dir']);
        $config->setProxyNamespace('App\Proxies');
        // In production, proxies are generated once (or ahead of time with orm:generate-proxies)
        $config->setAutoGenerateProxyClasses($this->settings['dev_mode']
            ? ProxyFactory::AUTOGENERATE_ALWAYS
            : ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        return $config;
    }

    public function createConnection(Configuration $config): Connection
    {
        if ($this->settings['connection']['driver'] === 'pdo_sqlite') {
            // SQLite ignores foreign keys (and ON DELETE CASCADE) unless enabled per connection
            $config->setMiddlewares([...$config->getMiddlewares(), new EnableForeignKeys()]);
        }

        return DriverManager::getConnection($this->connectionParams(), $config);
    }

    /**
     * @return array<string, mixed>
     */
    private function connectionParams(): array
    {
        $settings = $this->settings['connection'];
        $driver = $settings['driver'];

        if ($driver === 'pdo_sqlite') {
            if ($settings['path'] === ':memory:') {
                return ['driver' => $driver, 'memory' => true];
            }

            self::ensureDirectoryExists(dirname($settings['path']));

            return ['driver' => $driver, 'path' => $settings['path']];
        }

        if (!isset(self::DEFAULT_PORTS[$driver])) {
            throw new \InvalidArgumentException("Unsupported database driver: $driver");
        }

        $missing = array_filter(
            ['dbname' => 'DB_NAME', 'user' => 'DB_USER', 'password' => 'DB_PASSWORD'],
            static fn (string $key): bool => ($settings[$key] ?? null) === null,
            ARRAY_FILTER_USE_KEY
        );
        if ($missing !== []) {
            throw new \RuntimeException(
                sprintf('Missing environment variable(s) for %s: %s', $driver, implode(', ', $missing))
            );
        }

        return [
            'driver' => $driver,
            'host' => $settings['host'],
            'port' => (int) ($settings['port'] ?? self::DEFAULT_PORTS[$driver]),
            'dbname' => $settings['dbname'],
            'user' => $settings['user'],
            'password' => $settings['password'],
            'charset' => self::CHARSETS[$driver],
        ];
    }

    private function createCache(string $namespace): CacheItemPoolInterface
    {
        return $this->settings['dev_mode']
            ? new ArrayAdapter()
            : new PhpFilesAdapter("doctrine_$namespace", 0, $this->settings['cache_dir']);
    }

    private static function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Unable to create directory: $path");
        }
    }
}
