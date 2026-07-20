<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class PersistenceBag extends Bag
{
    public ?int $rdbBgsaveInProgress {
        get => $this->getInt('rdb_bgsave_in_progress');
    }

    // timestamp of the last save
    public ?int $rdbLastSaveTime {
        get => $this->getInt('rdb_last_save_time');
    }

    public ?int $rdbChangesSinceLastSave {
        get => $this->getInt('rdb_changes_since_last_save');
    }

    public ?string $rdbLastBgsaveStatus {
        get => $this->get('rdb_last_bgsave_status');
    }

    /**
     * The collector normalizes rdb_last_bgsave_time and rdb_last_bgsave_time_sec into rdb_last_bgsave_time
     */
    public ?int $rdbLastBgsaveTime {
        get => $this->getInt('rdb_last_bgsave_time');
    }

    public ?bool $aofEnabled {
        get => $this->getBool('aof_enabled');
    }

    public ?int $aofRewriteInProgress {
        get => $this->getInt('aof_rewrite_in_progress');
    }

    public ?int $aofLastRewriteTimeSec {
        get => $this->getInt('aof_last_rewrite_time_sec');
    }

    public ?string $aofLastBgrewriteStatus {
        get => $this->get('aof_last_bgrewrite_status');
    }

    public ?int $aofLastCowSize {
        get => $this->getInt('aof_last_cow_size');
    }

    public ?int $aofCurrentSize {
        get => $this->getInt('aof_current_size');
    }

    public ?int $aofRewriteBaseSize {
        get => $this->getInt('aof_rewrite_base_size');
    }

    public ?string $aofGrowthPercent {
        get {
            if (!$this->aofEnabled || !$this->aofRewriteBaseSize) {
                return null;
            }

            $delta = $this->aofCurrentSize - $this->aofRewriteBaseSize;

            $percent = round(($delta / $this->aofRewriteBaseSize) * 100, 2);

            if ($percent > 0) {
                $percent = '+' . $percent;
            }

            return $percent . '%';
        }
    }
}
