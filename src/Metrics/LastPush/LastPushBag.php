<?php

declare(strict_types=1);

namespace App\Metrics\LastPush;

use App\Metrics\Dto\Bag;

class LastPushBag extends Bag
{
    public ?\DateTimeImmutable $lastPushAt {
        get {
            return $this->lastPushAt ??= $this->get('received_at') ? new \DateTimeImmutable('@' . $this->get('received_at')) : null;
        }
    }

    public ?int $elapsedSeconds {
        get => $this->getInt('received_at') ? time() - $this->getInt('received_at') : null;
    }

    public ?string $collectorVersion {
        get => $this->get('collector_version');
    }
}
