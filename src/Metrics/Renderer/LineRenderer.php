<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Renderer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Renderer::Line->value)]
readonly class LineRenderer extends InfluxChartRenderer {}
