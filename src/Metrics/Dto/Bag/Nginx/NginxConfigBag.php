<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Nginx;

use App\Metrics\Dto\Bag;

class NginxConfigBag extends Bag
{
    public private(set) NginxBag $nginxBag;

    public function __construct(array $parameters, NginxBag $nginxBag)
    {
        parent::__construct($parameters);

        $this->nginxBag = $nginxBag;
    }

    public ?string $configPath {
        get => $this->get('config_path');
    }

    public ?string $user {
        get => $this->get('user');
    }

    /**
     * In nginx.conf, `worker_processes` can be `auto` or an integer.
     */
    public string|int $workerProcesses {
        get => $this->get('worker_processes') === 'auto' ? 'auto' : $this->getInt('worker_processes');
    }

    /**
     * @return string[]
     */
    public array $include {
        get => $this->all('include');
    }

    public ?int $workerConnections {
        get => $this->getInt('worker_connections');
    }

    /**
     * "on/off" Nginx directives
     */
    public ?string $sendfile {
        get => $this->get('sendfile');
    }

    public ?string $tcpNopush {
        get => $this->get('tcp_nopush');
    }

    public ?string $tcpNodelay {
        get => $this->get('tcp_nodelay');
    }

    /**
     * Usually in seconds (may also look like "65" or "65s" depending on the parser).
     * Here we assume the value is an int (or a castable string).
     */
    public ?int $keepaliveTimeout {
        get => $this->getInt('keepalive_timeout');
    }

    public ?int $typesHashMaxSize {
        get => $this->getInt('types_hash_max_size');
    }

    /**
     * `server_tokens on/off;`
     */
    public ?string $serverTokens {
        get => $this->get('server_tokens');
    }

    public ?bool $serverTokenEnabled {
        get => match ($this->serverTokens) {
            'on' => true,
            'off' => false,
            default => null,
        };
    }

    /**
     * Typically: "TLSv1.2 TLSv1.3" → list.
     *
     * @return string
     */
    public string $sslProtocols {
        get => $this->get('ssl_protocols');
    }

    public ?string $sslPreferServerCiphers {
        get => $this->get('ssl_prefer_server_ciphers');
    }

    /**
     * Can be a path or "off".
     */
    public ?string $accessLog {
        get => $this->get('access_log');
    }

    /**
     * Can be a path, sometimes followed by a level: "path warn".
     */
    public ?string $errorLog {
        get => $this->get('error_log');
    }

    public ?string $errorLogPath {
        get {
            if ($this->errorLog === null) {
                return null;
            }

            return explode(' ', $this->errorLog)[0];
        }
    }

    public ?string $errorLogLevel {
        get {
            if ($this->errorLog === null) {
                return null;
            }

            return explode(' ', $this->errorLog)[1] ?? null;
        }
    }

    public ?string $gzip {
        get => $this->get('gzip');
    }

    /**
     * Returns the max number of connections allowed by the nginx config.
     * MaxConnections = worker_processes x worker_connections
     * except that worker_processes can be "auto", in which case the CPU count is used
     */
    public ?int $maxConnections {
        get {
            $nbWorkerProcesses = $this->workerProcesses;
            if ($nbWorkerProcesses === 'auto') {
                $nbWorkerProcesses = $this->nginxBag->cpuCount;
            }

            if ($nbWorkerProcesses === null || $this->workerConnections === null) {
                return null;
            }

            return $nbWorkerProcesses * $this->workerConnections;
        }
    }
}
