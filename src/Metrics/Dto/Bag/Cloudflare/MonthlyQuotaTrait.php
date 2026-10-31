<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

/**
 * For a bag whose counters are month-to-date, keyed by a `month` of the form YYYY-MM (UTC).
 */
trait MonthlyQuotaTrait
{
    public ?string $month {
        get => $this->get('month');
    }

    public function resetsAt(): ?\DateTimeImmutable
    {
        if ($this->month === null) {
            return null;
        }

        $start = \DateTimeImmutable::createFromFormat('!Y-m', $this->month, new \DateTimeZone('UTC'));

        return $start === false ? null : $start->modify('+1 month');
    }
}
