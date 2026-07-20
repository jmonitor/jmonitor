<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Bridge\Eol\Dto\Cycle;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum OutdatedVersion: string implements TranslatableInterface
{
    case EOL = 'EOL';
    case DEPRECATED = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::EOL => 'End of life',
            self::DEPRECATED => 'Deprecated',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }

    public function isReachedBy(Cycle $cycle): bool
    {
        return match ($this) {
            self::EOL => $cycle->isEol(),
            self::DEPRECATED => $cycle->isSecurityFixOnly() || $cycle->isEol(),
        };
    }
}
