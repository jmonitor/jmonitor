<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use App\Bridge\InfluxDb\RetentionDuration;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Plan: string implements TranslatableInterface
{
    case FREE = 'free';
    case PRO = 'pro';
    case MAX = 'max';

    /**
     * Plan of the self-hosted edition (APP_EDITION=selfhosted): everything unlocked,
     * never purchasable nor shown on the pricing page (absent from orderedCases()).
     * Resolved by PlanResolver in the self-hosted edition, never stored in a Subscription.
     */
    case SELF_HOSTED = 'self_hosted';

    /**
     * Plans displayable on the pricing page and accepted as PlanVoter attributes.
     * SELF_HOSTED is deliberately excluded.
     */
    public static function orderedCases(): array
    {
        return [self::FREE, self::PRO, self::MAX];
    }

    /**
     * String-valued version of orderedCases(), e.g. for form choices.
     *
     * @return array<string>
     */
    public static function stringCases(): array
    {
        return [self::FREE->value, self::PRO->value, self::MAX->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PRO => 'Pro',
            self::MAX => 'Max',
            self::SELF_HOSTED => 'Self-hosted',
        };
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::FREE => 'Ideal for projects in development',
            self::PRO => 'Ideal for solo devs and agencies',
            self::MAX => 'Ideal for product teams',
            self::SELF_HOSTED => 'Self-hosted edition',
        };
    }

    public function subscribeMonthlyPrice(): int
    {
        return match ($this) {
            self::FREE => 0,
            self::PRO => 5,
            self::MAX => 10,
            self::SELF_HOSTED => 0,
        };
    }

    public function cardFeatures(): array
    {
        return match ($this) {
            self::FREE => [
                'Real-time monitoring<sup>*</sup>',
                'Push interval: 30s',
                'Invite collaborators',
            ],
            self::PRO => [
                'Everything from Free',
                'Push interval: 15s',
                'Alerting',
                'History charts',
                'Auto Refresh',
                'Embed metrics',
                'Data retention: 2 months',
            ],
            self::MAX => [
                'Everything from Pro',
                'Push interval: 10s',
                'Data retention: 6 months',
            ],
            self::SELF_HOSTED => [],
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FREE => 'server',
            self::PRO => 'server-content',
            self::MAX, self::SELF_HOSTED => 'server-moustaches',
        };
    }

    /**
     * so plans can be compared in a voter for example
     */
    public function score(): int
    {
        return match ($this) {
            self::FREE => 1,
            self::PRO => 2,
            self::MAX => 3,
            self::SELF_HOSTED => 4,
        };
    }

    // for easyadmin
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }

    /**
     * Paid cloud plans that can be bought through Stripe checkout.
     */
    public function isPurchasable(): bool
    {
        return match ($this) {
            self::PRO, self::MAX => true,
            default => false,
        };
    }

    public function autoRefresh(): bool
    {
        return match ($this) {
            self::FREE => false,
            default => true,
        };
    }

    public function historyChart(): bool
    {
        return match ($this) {
            self::FREE => false,
            default => true,
        };
    }

    public function alerting(): bool
    {
        return match ($this) {
            self::FREE => false,
            default => true,
        };
    }

    public function pushInterval(): int
    {
        return match ($this) {
            self::FREE => 30,
            self::PRO => 15,
            self::MAX, self::SELF_HOSTED => 10,
        };
    }

    public function embedable(): bool
    {
        return match ($this) {
            self::FREE => false,
            default => true,
        };
    }

    public function noAds(): bool
    {
        return match ($this) {
            self::FREE => false,
            default => true,
        };
    }

    public function dataRetention(): string
    {
        return match ($this) {
            self::FREE => '',
            self::PRO => '2 months',
            self::MAX => '6 months',
            self::SELF_HOSTED => '1 year',
        };
    }

    public function upgradeTo(): ?self
    {
        return match ($this) {
            self::FREE => self::PRO,
            self::PRO => self::MAX,
            default => null,
        };
    }

    public function nbRedisDb(): int
    {
        return match ($this) {
            self::FREE => 1,
            self::PRO => 4,
            self::MAX => 16,
            self::SELF_HOSTED => PHP_INT_MAX,
        };
    }

    public function nbFrankenPhpWorkers(): int
    {
        return match ($this) {
            self::FREE => 1,
            self::PRO => 4,
            self::MAX => 16,
            self::SELF_HOSTED => PHP_INT_MAX,
        };
    }

    public function influxDataRetentionSecond(): int
    {
        return match ($this) {
            self::FREE => throw new \Exception('Free plan has no retention'),
            self::PRO => RetentionDuration::MONTH->asSeconds(2),
            self::MAX => RetentionDuration::MONTH->asSeconds(6),
            self::SELF_HOSTED => RetentionDuration::YEAR->asSeconds(1),
        };
    }
}
