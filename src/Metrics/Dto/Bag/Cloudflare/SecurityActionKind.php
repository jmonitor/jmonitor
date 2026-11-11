<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

/**
 * What a Cloudflare security action did to the request. Cloudflare spells the same action differently
 * from one dataset to the next (managed_challenge, managedChallenge, managedchallenge).
 */
enum SecurityActionKind
{
    case Block;
    case Challenge;
    case Passive;

    private const array BLOCKS = ['block', 'connectionclose', 'forceconnectionclose'];
    private const array CHALLENGES = ['challenge', 'jschallenge', 'managedchallenge', 'precursorinterstitialpageissued'];

    public static function of(string $action): self
    {
        $action = self::normalize($action);

        return match (true) {
            in_array($action, self::BLOCKS, true) => self::Block,
            in_array($action, self::CHALLENGES, true) => self::Challenge,
            default => self::Passive,
        };
    }

    public static function normalize(string $identifier): string
    {
        return strtolower(str_replace('_', '', $identifier));
    }

    public function isMitigation(): bool
    {
        return $this !== self::Passive;
    }

    public function barClass(): string
    {
        return match ($this) {
            self::Block => 'bg-danger',
            self::Challenge => 'bg-warning',
            self::Passive => 'bg-secondary',
        };
    }
}
