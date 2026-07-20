<?php

declare(strict_types=1);

namespace App\Tests\Install;

use App\Install\EnvChecker;
use PHPUnit\Framework\TestCase;

/**
 * app:install refuses to boot a self-hosted instance whose .env.example
 * placeholders were left in place (CHANGE_ME) or whose secrets are structurally
 * invalid (Mercure requires a >= 256-bit HMAC key).
 */
final class EnvCheckerTest extends TestCase
{
    private function checker(
        string $appSecret = 'a-real-app-secret',
        string $mercureJwtSecret = 'a-real-mercure-secret-of-32-chars!',
        string $adminEmail = 'admin@example.com',
        string $adminPassword = 'a-real-password',
        string $mysqlRootPassword = 'a-real-mysql-password',
        string $influxdbToken = 'a-real-influxdb-token',
        string $influxdbAdminPassword = 'a-real-influxdb-admin-password',
        string $timezone = 'UTC',
    ): EnvChecker {
        return new EnvChecker($appSecret, $mercureJwtSecret, $adminEmail, $adminPassword, $mysqlRootPassword, $influxdbToken, $influxdbAdminPassword, $timezone);
    }

    public function testValidConfigYieldsNoErrors(): void
    {
        self::assertSame([], $this->checker()->check());
    }

    public function testEmptyAppSecretIsReported(): void
    {
        $errors = $this->checker(appSecret: '')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('APP_SECRET', $errors[0]);
    }

    public function testPlaceholderAppSecretIsReported(): void
    {
        $errors = $this->checker(appSecret: 'CHANGE_ME')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('APP_SECRET', $errors[0]);
    }

    public function testPlaceholderMercureSecretIsReported(): void
    {
        // Long enough to pass the length rule: the placeholder must still be caught.
        $errors = $this->checker(mercureJwtSecret: 'CHANGE_ME_padded_to_32_characters!!')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('MERCURE_JWT_SECRET', $errors[0]);
    }

    public function testTooShortMercureSecretIsReported(): void
    {
        $errors = $this->checker(mercureJwtSecret: 'short-secret')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('MERCURE_JWT_SECRET', $errors[0]);
    }

    public function testPlaceholderAdminCredentialsAreReported(): void
    {
        $errors = $this->checker(adminEmail: 'CHANGE_ME@example.com', adminPassword: 'CHANGE_ME')->check();

        self::assertCount(2, $errors);
        self::assertStringContainsString('ADMIN_EMAIL', $errors[0]);
        self::assertStringContainsString('ADMIN_PASSWORD', $errors[1]);
    }

    public function testEmptyAdminCredentialsAreAllowed(): void
    {
        // Empty is valid here: an admin may already exist (AdminProvisioner decides).
        self::assertSame([], $this->checker(adminEmail: '', adminPassword: '')->check());
    }

    public function testPlaceholderMysqlRootPasswordIsReported(): void
    {
        $errors = $this->checker(mysqlRootPassword: 'CHANGE_ME')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('MYSQL_ROOT_PASSWORD', $errors[0]);
    }

    public function testPlaceholderInfluxdbTokenIsReported(): void
    {
        $errors = $this->checker(influxdbToken: 'CHANGE_ME')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('INFLUXDB_TOKEN', $errors[0]);
    }

    public function testPlaceholderInfluxdbAdminPasswordIsReported(): void
    {
        $errors = $this->checker(influxdbAdminPassword: 'CHANGE_ME')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('INFLUXDB_ADMIN_PASSWORD', $errors[0]);
    }

    public function testEmptyComposeStackSecretsAreAllowed(): void
    {
        // Empty is valid here too: these vars are consumed by the compose stack
        // (mysql/influxdb containers), not always by the app itself.
        self::assertSame([], $this->checker(mysqlRootPassword: '', influxdbToken: '', influxdbAdminPassword: '')->check());
    }

    public function testValidTimezoneIdentifierIsAllowed(): void
    {
        self::assertSame([], $this->checker(timezone: 'Europe/Paris')->check());
    }

    public function testInvalidTimezoneIdentifierIsReported(): void
    {
        $errors = $this->checker(timezone: 'Europe/Pariss')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('TZ', $errors[0]);
    }

    public function testOffsetTimezoneIsRejected(): void
    {
        // PHP would accept "+02:00" but glibc (mysql container) would not:
        // only IANA identifiers are valid for the whole stack.
        $errors = $this->checker(timezone: '+02:00')->check();

        self::assertCount(1, $errors);
        self::assertStringContainsString('TZ', $errors[0]);
    }

    public function testEmptyTimezoneIsAllowed(): void
    {
        // Empty falls back to UTC in the container entrypoint.
        self::assertSame([], $this->checker(timezone: '')->check());
    }

    public function testAllErrorsAreAccumulated(): void
    {
        $errors = $this->checker(appSecret: '', mercureJwtSecret: 'short', adminPassword: 'CHANGE_ME')->check();

        self::assertCount(3, $errors);
    }
}
