<?php

use Symfony\Component\Dotenv\Dotenv;

// Load dotenv if not already loaded and .env exists (idempotent)
if (!isset($_ENV['APP_ENV']) && file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development';
$debugEnv = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? null;
$debug = $debugEnv !== null
    ? in_array(strtolower((string)$debugEnv), ['1','true','yes','on'], true)
    : ($env !== 'production');

// Helper to parse CSV from env
$csv = static function (?string $value, array $default = []): array {
    if ($value === null || trim($value) === '') {
        return $default;
    }
    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn($v) => $v !== ''));
};

$settings = [
    'env' => $env,
    'debug' => $debug,
    'displayErrorDetails' => $debug,

    'cors' => [
        'origins' => $csv($_ENV['CORS_ORIGINS'] ?? null, ['*']),
        'methods' => $csv($_ENV['CORS_METHODS'] ?? null, ['GET','POST','PUT','PATCH','DELETE','OPTIONS']),
        'headers' => $csv($_ENV['CORS_HEADERS'] ?? null, ['Content-Type','Authorization','Accept','Origin','X-Requested-With']),
        'expose_headers' => $csv($_ENV['CORS_EXPOSE_HEADERS'] ?? null, []),
        'credentials' => isset($_ENV['CORS_CREDENTIALS']) ? in_array(strtolower((string)$_ENV['CORS_CREDENTIALS']), ['1','true','yes','on'], true) : false,
        'max_age' => isset($_ENV['CORS_MAX_AGE']) ? (int)$_ENV['CORS_MAX_AGE'] : 600,
    ],

    'validation' => [
        // placeholder for global validation settings if needed later
    ],
];

// Per-environment override file: config/settings.{env}.php
$overridePath = __DIR__ . "/settings.$env.php";
if (is_file($overridePath)) {
    /** @var array $override */
    $override = require $overridePath;
    $settings = array_replace_recursive($settings, $override);
}

return $settings;
