<?php

namespace App\Config;

final class Environment
{
    private const TRUTHY = ['1', 'true', 'yes', 'on'];

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
        return self::bool('APP_DEBUG', self::name() !== 'production');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value === false ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value !== null
            ? in_array(strtolower((string) $value), self::TRUTHY, true)
            : $default;
    }

    /**
     * Reads a comma-separated variable, e.g. "GET, POST" => ['GET', 'POST'].
     *
     * @param list<string> $default
     *
     * @return list<string>
     */
    public static function list(string $key, array $default = []): array
    {
        $value = trim((string) self::get($key, ''));

        if ($value === '') {
            return $default;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $v): bool => $v !== ''));
    }
}
