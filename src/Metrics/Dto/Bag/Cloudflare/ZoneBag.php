<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class ZoneBag extends Bag
{
    public ?string $name {
        get => $this->get('name');
    }

    public ?string $plan {
        get => $this->get('plan');
    }

    public ?string $status {
        get => $this->get('status');
    }

    public bool $paused {
        get => $this->getBool('paused', false) ?? false;
    }

    /**
     * Seconds of development mode left, 0 when off.
     */
    public int $developmentMode {
        get => $this->getInt('development_mode', 0) ?? 0;
    }

    /**
     * @var array<string, mixed>
     */
    public array $settings {
        get => is_array($this->get('settings')) ? $this->all('settings') : [];
    }

    public bool $hasSettings {
        get => is_array($this->get('settings'));
    }

    public ?CertificateBag $certificate {
        get => $this->certificate ??= is_array($this->get('certificate')) ? new CertificateBag($this->all('certificate')) : null;
    }

    public ?int $dnsRecordsUsed {
        get => is_array($this->get('dns_records')) ? new Bag($this->all('dns_records'))->getInt('used') : null;
    }

    public ?int $dnsRecordsQuota {
        get => is_array($this->get('dns_records')) ? new Bag($this->all('dns_records'))->getInt('quota') : null;
    }

    public ?\DateTimeImmutable $fetchedAt {
        get => ($t = $this->getInt('fetched_at')) === null ? null : new \DateTimeImmutable('@' . $t);
    }
}
