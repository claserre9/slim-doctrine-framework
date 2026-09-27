<?php

namespace App\Config;

final class Environment
{
    public static function name(): string
    {
        return (string) self::get('APP_ENV', 'production');
    }

    public static function isDevelopment(): bool
    {
        return self::name() === 'development';
    }

    public static function isDebug(): bool
    {
        $debug = self::get('APP_DEBUG');

        return $debug !== null
            ? in_array(strtolower((string) $debug), ['1', 'true', 'yes', 'on'], true)
            : self::name() !== 'production';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value === false ? $default : $value;
    }
}
