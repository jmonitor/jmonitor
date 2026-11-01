<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class TrafficBag extends Bag
{
    public int $from {
        get => $this->getInt('from', 0) ?? 0;
    }

    public int $to {
        get => $this->getInt('to', 0) ?? 0;
    }

    public int $spanMinutes {
        get => intdiv(max(0, $this->to - $this->from), 60);
    }

    /**
     * @var TrafficMinute[]
     */
    public array $minutes {
        get => $this->minutes ??= array_values(array_filter(array_map(
            fn(mixed $minute): ?TrafficMinute => is_array($minute) ? TrafficMinute::fromArray($minute) : null,
            is_array($this->get('minutes')) ? $this->all('minutes') : [],
        )));
    }

    public WindowBag $window {
        get => $this->window ??= new WindowBag(is_array($this->get('window')) ? $this->all('window') : []);
    }

    public function cachedBytesRatio(): ?float
    {
        $bytes = array_sum(array_map(fn(TrafficMinute $m): int => $m->bytes, $this->minutes));

        if ($bytes === 0) {
            return null;
        }

        return array_sum(array_map(fn(TrafficMinute $m): int => $m->cachedBytes, $this->minutes)) / $bytes;
    }

    public function originRequests(): int
    {
        return array_sum(array_map(fn(TrafficMinute $m): int => $m->originRequests ?? 0, $this->minutes));
    }

    /**
     * The most recent minute that reached the origin.
     */
    public function latestOrigin(): ?TrafficMinute
    {
        foreach (array_reverse($this->minutes) as $minute) {
            if ($minute->originResponseMsP50 !== null) {
                return $minute;
            }
        }

        return null;
    }
}
