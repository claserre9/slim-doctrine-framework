<?php

use App\Config\Environment;

$root = dirname(__DIR__);

$resolvePath = static fn (string $path): string => str_starts_with($path, '/') ? $path : $root . '/' . $path;

$settings = [
    'env'   => Environment::name(),
    'debug' => Environment::isDebug(),
    'root'  => $root,

    'cors' => [
        'origins'        => Environment::list('CORS_ORIGINS', ['*']),
        'methods'        => Environment::list('CORS_METHODS', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']),
        'headers'        => Environment::list('CORS_HEADERS', ['Content-Type', 'Authorization', 'Accept', 'Origin', 'X-Requested-With']),
        'expose_headers' => Environment::list('CORS_EXPOSE_HEADERS'),
        'credentials'    => Environment::bool('CORS_CREDENTIALS'),
        'max_age'        => (int) Environment::get('CORS_MAX_AGE', 600),
    ],

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
        'table_storage'    => ['table_name' => 'doctrine_migration_versions'],
        'migrations_paths' => ['DoctrineMigrations' => $root . '/migrations'],
    ],
];

// Per-environment overrides: config/settings.{env}.php returns a partial settings array
$overridePath = __DIR__ . '/settings.' . $settings['env'] . '.php';
if (is_file($overridePath)) {
    $settings = array_replace_recursive($settings, require $overridePath);
}

return $settings;
