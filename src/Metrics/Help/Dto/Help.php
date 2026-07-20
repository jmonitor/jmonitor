<?php

declare(strict_types=1);

namespace App\Metrics\Help\Dto;

class Help
{
    public private(set) array $definitions = [];
    public private(set) array $whyItMatters = [];
    public private(set) array $howToRead = [];
    public private(set) array $actions = [];
    public private(set) array $goodToKnow = [];

    public function __construct(array $definitions, array $whyItMatters, array $howToRead, array $actions, array $goodToKnow)
    {
        $this->definitions = $definitions;
        $this->whyItMatters = $whyItMatters;
        $this->howToRead = $howToRead;
        $this->actions = $actions;
        $this->goodToKnow = $goodToKnow;
    }
}
