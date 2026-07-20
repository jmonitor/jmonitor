<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\Bag;
use DateTimeImmutable;

class SchedulerTaskBag extends Bag
{
    public ?string $trigger {
        get => $this->trigger ??= $this->parseTriggerAndJitter()['trigger'];
    }

    public ?string $jitter {
        get => $this->jitter ??= $this->parseTriggerAndJitter()['jitter'];
    }

    public ?string $command {
        get => $this->get('command');
    }

    public ?int $nextRun {
        get => $this->getInt('next_run');
    }

    public ?DateTimeImmutable $nextRunAt {
        get => $this->nextRun ? new DateTimeImmutable('@' . $this->nextRun) : null;
    }

    public ?string $description {
        get => $this->get('description');
    }

    /**
     * Exemples:
     * - "every 3 hour with 0-10 second jitter" => trigger="every 3 hour", jitter="0-10 second"
     * - "every 3 hour" => trigger="every 3 hour", jitter=null
     * @return array{trigger: string|null, jitter: string|null}
     */
    private function parseTriggerAndJitter(): array
    {
        $raw = $this->get('trigger');
        if ($raw === null) {
            return ['trigger' => null, 'jitter' => null];
        }

        $matches = [];
        $ok = (bool) preg_match(
            '/^(?<trigger>.+?)(?:\s+with\s+(?<jitter>.+?)\s+jitter)?\s*$/i',
            $raw,
            $matches,
        );

        if (!$ok) {
            return ['trigger' => $raw, 'jitter' => null];
        }

        $trigger = trim((string) $matches['trigger']) ?: null;
        $jitter = isset($matches['jitter']) ? trim((string) $matches['jitter']) : null;

        return [
            'trigger' => $trigger,
            'jitter' => $jitter ?: null,
        ];
    }
}
