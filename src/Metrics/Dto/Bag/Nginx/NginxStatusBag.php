<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Nginx;

use App\Metrics\Dto\Bag;

class NginxStatusBag extends Bag
{
    public private(set) NginxBag $nginxBag;

    public function __construct(array $parameters, NginxBag $nginxBag)
    {
        parent::__construct($parameters);

        $this->nginxBag = $nginxBag;
    }

    /**
     * Total number of client connections currently open
     * Active = Reading + Writing + Waiting
     */
    public ?int $activeConnections {
        get => $this->getInt('active');
    }

    /**
     * Cumulative since Nginx started
     * Total number of accepted connections
     */
    public ?int $acceptedConnections {
        get => $this->getInt('accepts');
    }

    /**
     * Cumulative since Nginx started
     * Total number of connections actually handled; if it equals accepts, everything is fine
     */
    public ?int $handledRequests {
        get => $this->getInt('handled');
    }

    /**
     * Cumulative since Nginx started
     * Total number of refused connections, in theory because the max was reached
     */
    public ?int $refusedRequests {
        get => $this->acceptedConnections !== null && $this->handledRequests !== null
            ? $this->acceptedConnections - $this->handledRequests
            : null;
    }

    /**
     * Cumulative since Nginx started
     * Total number of HTTP requests handled
     */
    public ?int $httpRequests {
        get => $this->getInt('requests');
    }

    /**
     * Computed from the delta of requests
     */
    public ?float $requestsPerSecond {
        get => $this->get('requests_per_second');
    }

    /**
     * Connections where Nginx is reading the client request headers
     * Low = normal (reading is very fast)
     */
    public ?int $readingConnections {
        get => $this->getInt('reading');
    }

    /**
     * Connections where Nginx is sending the response to the client
     * Can be high with large files, slow clients, ...
     */
    public ?int $writingConnections {
        get => $this->getInt('writing');
    }

    /**
     * Idle keep-alive connections
     * Request finished, connection kept open waiting for a new request
     */
    public ?int $waitingConnections {
        get => $this->getInt('waiting');
    }

    /**
     * Value from 1 upwards
     * Ratio = 1: each request opens a new connection (Keep-Alive ineffective or absent).
     * Ratio > 1: Keep-Alive is working. The higher the value, the more connections are reused.
     */
    public ?float $keepAliveRatio {
        get => $this->httpRequests !== null && $this->handledRequests > 0
            ? round($this->httpRequests / $this->handledRequests, 2)
            : null
        ;
    }

    /**
     * Ratio of waiting connections to active connections
     * The higher it is, the more open idle connections the server is keeping around.
     */
    public ?float $waitingRequestsPercent {
        get => $this->waitingConnections !== null && $this->activeConnections > 0
            ? round($this->waitingConnections / $this->activeConnections * 100, 2)
            : null;
    }

    /**
     * Number of active connections relative to the maximum allowed by nginx
     */
    public ?float $activeConnectionsPercent {
        get {
            $max = $this->nginxBag->config->maxConnections;

            if ($max <= 0 || $this->activeConnections === null) {
                return null;
            }

            return round($this->activeConnections / $max * 100, 2);
        }
    }
}
