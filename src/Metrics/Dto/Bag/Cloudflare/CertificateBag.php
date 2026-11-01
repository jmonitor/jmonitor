<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class CertificateBag extends Bag
{
    public ?string $status {
        get => $this->get('status');
    }

    public ?string $issuer {
        get => $this->get('issuer');
    }

    public ?\DateTimeImmutable $expiresAt {
        get => ($t = $this->getInt('expires_at')) === null ? null : new \DateTimeImmutable('@' . $t);
    }

    /**
     * @var string[]
     */
    public array $hosts {
        get => is_array($this->get('hosts')) ? array_values(array_map('strval', $this->all('hosts'))) : [];
    }

    /**
     * Whole days before expiry, negative once expired.
     */
    public function daysLeft(\DateTimeImmutable $now): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        return (int) floor(($this->expiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
    }
}
