<?php

declare(strict_types=1);

namespace App\Install;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fail-fast validation of the environment a self-hosted instance boots with
 * (run by app:install before anything touches the database). Catches
 * .env.example placeholders left in place, structurally invalid secrets, and
 * malformed settings such as a non-IANA TZ.
 */
readonly class EnvChecker
{
    private const string PLACEHOLDER = 'CHANGE_ME';

    /** lcobucci/jwt enforces an HMAC-SHA256 key of at least 256 bits. */
    private const int MERCURE_SECRET_MIN_LENGTH = 32;

    public function __construct(
        #[Autowire(env: 'APP_SECRET')]
        private string $appSecret,
        #[Autowire(env: 'MERCURE_JWT_SECRET')]
        private string $mercureJwtSecret,
        #[Autowire(env: 'ADMIN_EMAIL')]
        private string $adminEmail,
        #[Autowire(env: 'ADMIN_PASSWORD')]
        private string $adminPassword,
        #[Autowire(env: 'MYSQL_ROOT_PASSWORD')]
        private string $mysqlRootPassword = '',
        #[Autowire(env: 'INFLUXDB_TOKEN')]
        private string $influxdbToken = '',
        #[Autowire(env: 'INFLUXDB_ADMIN_PASSWORD')]
        private string $influxdbAdminPassword = '',
        #[Autowire(env: 'TZ')]
        private string $timezone = 'UTC',
    ) {}

    /**
     * @return list<string> human-readable errors, empty when the config is valid
     */
    public function check(): array
    {
        $errors = [];

        if ($this->appSecret === '' || str_contains($this->appSecret, self::PLACEHOLDER)) {
            $errors[] = 'APP_SECRET is empty or still a placeholder — generate one with: openssl rand -hex 16';
        }

        if (str_contains($this->mercureJwtSecret, self::PLACEHOLDER) || strlen($this->mercureJwtSecret) < self::MERCURE_SECRET_MIN_LENGTH) {
            $errors[] = sprintf('MERCURE_JWT_SECRET must be at least %d characters and not a placeholder — generate one with: openssl rand -hex 32', self::MERCURE_SECRET_MIN_LENGTH);
        }

        // Empty admin credentials are allowed (an admin may already exist —
        // AdminProvisioner decides); a leftover placeholder never is.
        if (str_contains($this->adminEmail, self::PLACEHOLDER)) {
            $errors[] = 'ADMIN_EMAIL is still a placeholder — set the email of your initial admin account.';
        }

        if (str_contains($this->adminPassword, self::PLACEHOLDER)) {
            $errors[] = 'ADMIN_PASSWORD is still a placeholder — set your own value.';
        }

        // Same rule as the admin credentials above: empty is allowed (these vars are
        // consumed by the compose stack — mysql/influxdb containers — not always by
        // the app itself), but a leftover placeholder never is.
        if (str_contains($this->mysqlRootPassword, self::PLACEHOLDER)) {
            $errors[] = 'MYSQL_ROOT_PASSWORD is still a placeholder — set your own value.';
        }

        if (str_contains($this->influxdbToken, self::PLACEHOLDER)) {
            $errors[] = 'INFLUXDB_TOKEN is still a placeholder — set your own value.';
        }

        if (str_contains($this->influxdbAdminPassword, self::PLACEHOLDER)) {
            $errors[] = 'INFLUXDB_ADMIN_PASSWORD is still a placeholder — set your own value.';
        }

        // Empty is allowed (the entrypoint falls back to UTC). Only IANA
        // identifiers are valid: the value feeds both PHP's date.timezone ini
        // setting and the mysql container's glibc, and neither accepts the raw
        // offsets ("+02:00") that PHP's DateTimeZone constructor would.
        if ($this->timezone !== '' && !in_array($this->timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            $errors[] = sprintf('TZ "%s" is not a valid IANA timezone identifier — e.g. Europe/Paris, America/New_York, UTC.', $this->timezone);
        }

        return $errors;
    }
}
