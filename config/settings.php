<?php

use App\Config\Environment;

$root = dirname(__DIR__);

$resolvePath = static fn (string $path): string => str_starts_with($path, '/') ? $path : $root . '/' . $path;

return [
    'env'   => Environment::name(),
    'debug' => Environment::isDebug(),
    'root'  => $root,

    'doctrine' => [
        'dev_mode'    => Environment::isDevelopment(),
        'entity_dirs' => [$root . '/src/Entities'],
        'cache_dir'   => $root . '/var/cache/doctrine',
        'proxy_dir'   => $root . '/var/cache/doctrine/proxies',
        'connection'  => [
            'driver'   => Environment::get('DB_DRIVER', 'pdo_sqlite'),
            'path'     => $resolvePath((string) Environment::get('DB_PATH', 'var/data/database.sqlite')),
            'host'     => Environment::get('DB_HOST', 'localhost'),
            'port'     => Environment::get('DB_PORT'),
            'dbname'   => Environment::get('DB_NAME'),
            'user'     => Environment::get('DB_USER'),
            'password' => Environment::get('DB_PASSWORD'),
        ],
    ],

    'migrations' => [
        'table_storage'   => ['table_name' => 'doctrine_migration_versions'],
        'migrations_paths' => ['DoctrineMigrations' => $root . '/migrations'],
    ],
];
