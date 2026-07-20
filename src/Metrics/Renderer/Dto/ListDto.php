<?php

namespace App\Metrics\Renderer\Dto;

use App\Metrics\Consumer\Consumer;

class ListDto extends AbstractDto
{
    /** @var Consumer[] */
    public private(set) array $consumers;

    /**
     * @param Consumer[] $consumers
     * @return $this
     */
    public function setConsumers(array $consumers): self
    {
        $this->consumers = $consumers;

        return $this;
    }
}
