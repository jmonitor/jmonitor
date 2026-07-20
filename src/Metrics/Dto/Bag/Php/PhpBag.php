<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php;

use App\Metrics\Dto\Bag\Php\Apcu\ApcuBag;
use App\Metrics\Dto\Bag\Php\Opcache\FpmBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Dto\Bag\Php\Opcache\OpcacheBag;

class PhpBag extends MetricBagDto
{
    public ?string $phpVersion {
        get => $this->get('version');
    }

    public ?string $sapiName {
        get => $this->get('sapi_name');
    }

    public ?string $iniFile {
        get => $this->get('ini_file') ?: null; // php_ini_loaded_file() returns false when no ini file is loaded
    }

    /**
     * @var string[]
     */
    public array $iniFiles {
        get => $this->all('ini_files');
    }

    public ?bool $enabled {
        get => $this->getBool('expose_php');
    }

    public int|false|null $memoryLimit {
        get {
            $value = $this->getIniParsedInt('memory_limit');

            return $value === -1 ? false : $value;
        }
    }

    public ?int $maxExecutionTime {
        get => $this->getInt('max_execution_time');
    }

    public ?int $maxInputTime {
        get => $this->getInt('max_input_time');
    }

    public ?int $maxInputVars {
        get => $this->getInt('max_input_vars');
    }

    public ?int $realpathUsedCacheSize {
        get => $this->getInt('realpath_cache_size');
    }

    public ?int $realpathCacheSize {
        get {
            $size = $this->get('realpath_cache_size');

            if (is_string($size)) {
                return $this->getIniParsedInt('realpath_cache_size');
            }

            return $size;
        }
    }

    public ?int $realpathCacheTtl {
        get => $this->getInt('realpath_cache_ttl');
    }

    // raw php.ini shorthand (e.g. "8M"), displayed as-is
    public ?string $postMaxSize {
        get => $this->get('post_max_size');
    }

    // raw shorthand, displayed as-is
    public ?string $uploadMaxFilesize {
        get => $this->get('upload_max_filesize');
    }

    // can be stderr
    public ?string $displayErrors {
        get => $this->get('display_errors');
    }

    public ?bool $displayStartupErrors {
        get => $this->getBool('display_startup_errors');
    }

    public ?bool $logErrors {
        get => $this->getBool('log_errors');
    }

    public ?string $errorLog {
        get => $this->get('error_log');
    }

    public ?int $errorReporting {
        get => $this->getInt('error_reporting');
    }

    /**
     * Returns the error_reporting configuration in a readable form (e.g. "E_ERROR | E_WARNING | E_PARSE").
     *
     * Rules:
     * - null -> null (value not provided)
     * - 0 -> "0" (no level enabled)
     * - value matching E_ALL exactly -> "E_ALL"
     * - otherwise -> list of enabled constants separated by " | "
     */
    public function errorReportingString(): ?string
    {
        $level = $this->errorReporting;

        if ($level === null) {
            return null;
        }

        if ($level === 0) {
            return '0';
        }

        if (defined('E_ALL') && $level === E_ALL) {
            return 'E_ALL';
        }

        // map of the error_reporting constants relevant on PHP 8.x
        $map = [
            'E_ERROR'             => \E_ERROR,
            'E_WARNING'           => \E_WARNING,
            'E_PARSE'             => \E_PARSE,
            'E_NOTICE'            => \E_NOTICE,
            'E_CORE_ERROR'        => \E_CORE_ERROR,
            'E_CORE_WARNING'      => \E_CORE_WARNING,
            'E_COMPILE_ERROR'     => \E_COMPILE_ERROR,
            'E_COMPILE_WARNING'   => \E_COMPILE_WARNING,
            'E_USER_ERROR'        => \E_USER_ERROR,
            'E_USER_WARNING'      => \E_USER_WARNING,
            'E_USER_NOTICE'       => \E_USER_NOTICE,
            // E_STRICT no longer exists in PHP 8, guard it with defined()
            ...(defined('E_STRICT') ? ['E_STRICT' => \E_STRICT] : []),
            'E_RECOVERABLE_ERROR' => \E_RECOVERABLE_ERROR,
            'E_DEPRECATED'        => \E_DEPRECATED,
            'E_USER_DEPRECATED'   => \E_USER_DEPRECATED,
        ];

        $enabled = [];
        foreach ($map as $name => $const) {
            if (($level & $const) !== 0) {
                $enabled[] = $name;
            }
        }

        // if the value covers all known bits and is equivalent to E_ALL, return E_ALL
        // (useful when E_ALL == 32767 but the value carries exactly those bits)
        if (defined('E_ALL')) {
            $allKnown = 0;
            foreach ($map as $const) {
                $allKnown |= $const;
            }
            if (($level & E_ALL) === E_ALL && ($level | $allKnown) === $level) {
                return 'E_ALL';
            }
        }

        // expanded form (plain list)
        $expanded = $enabled ? implode(' | ', $enabled) : (string) $level;

        // try a compact form starting from E_ALL and excluding values,
        // only when the level is a subset of E_ALL (no bits outside E_ALL)
        if (defined('E_ALL') && ($level & ~E_ALL) === 0) {
            $excluded = [];
            foreach ($map as $name => $const) {
                // only consider constants included in E_ALL
                if ((E_ALL & $const) !== 0 && ($level & $const) === 0) {
                    $excluded[] = $name;
                }
            }

            if (!empty($excluded)) {
                $compact = 'E_ALL & ~' . implode(' & ~', $excluded);

                // return the shortest form (goal: concise display)
                return strlen($compact) < strlen($expanded) ? $compact : $expanded;
            }

            // no exclusion would mean E_ALL, already handled above by the exact-match case
        }

        return $expanded;
    }

    public ?string $dateTimezone {
        get => $this->get('date.timezone');
    }

    /**
     * @var string[]
     */
    public array $loadedExtensions {
        get => $this->all('loaded_extensions');
    }

    public private(set) OpcacheBag $opcache {
        get => $this->opcache ??= new OpcacheBag($this->all('opcache'));
    }

    public private(set) ApcuBag $apcu {
        get => $this->apcu ??= new ApcuBag($this->all('apcu'));
    }

    public private(set) FpmBag $fpm {
        get => $this->fpm ??= new FpmBag($this->all('fpm'), $this->memoryLimit);
    }
}
