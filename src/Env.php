<?php

declare(strict_types=1);

namespace App;

/**
 * Registry of available environments
 */
enum Env: string
{
    case DEV = 'dev';
    case PROD = 'prod';

    public static function current(): self
    {
        return self::from($_SERVER['APP_ENV']);
    }

    public static function isProd(): bool
    {
        return self::current() === self::PROD;
    }
}
