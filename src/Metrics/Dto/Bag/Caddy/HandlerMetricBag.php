<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Caddy;

use App\Metrics\Dto\Bag;

class HandlerMetricBag extends Bag
{
    public float|int|null $php {
        get => $this->get('php');
    }

    public float|int|null $fileServer {
        get => $this->get('file_server ');
    }

    public float|int|null $staticResponse {
        get => $this->get('static_response ');
    }
}
