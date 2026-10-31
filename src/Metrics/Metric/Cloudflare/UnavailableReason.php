<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

/**
 * Why a Cloudflare block is missing from the payload, in the words shown on its card.
 */
enum UnavailableReason: string
{
    case Forbidden = 'forbidden';
    case NotConfigured = 'not_configured';
    case RateLimited = 'rate_limited';
    case Error = 'error';
    case CollectorDown = 'collector_down';

    public const string TOKEN_DOC_URL = 'https://github.com/jmonitor/collector?tab=readme-ov-file#cloudflare';

    public static function fromPayload(?string $reason): self
    {
        return self::tryFrom((string) $reason) ?? self::Error;
    }

    public function message(): string
    {
        return match ($this) {
            self::Forbidden => 'The API token lacks the permission for this block.',
            self::NotConfigured => 'Not configured on this Cloudflare account.',
            self::RateLimited => 'Cloudflare rate limit reached, retrying.',
            self::Error => 'Cloudflare did not answer, retrying.',
            self::CollectorDown => 'The agent could not start the Cloudflare collector: the token was rejected or the zone was not found. Check the agent logs.',
        };
    }

    public function link(): ?string
    {
        return $this === self::Forbidden ? self::TOKEN_DOC_URL : null;
    }
}
