<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Error;

/**
 * Not a bug: there is simply no data to display for this metric.
 */
class EmptyDataException extends RenderingException {}
