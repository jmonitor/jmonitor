<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Error;

/**
 * Generic error while attempting to render (e.g. InfluxDB not responding).
 */
class RenderingException extends \Exception {}
