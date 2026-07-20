<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Bag
{
    /**
     * @param mixed[] $parameters
     */
    public function __construct(
        protected array $parameters = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return \array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    public function getInt(string $key, ?int $default = null): ?int
    {
        $value = $this->get($key, $default);

        if ($value === $default) {
            return $default;
        }

        return (int) $value;
    }

    public function getIniParsedInt(string $key): ?int
    {
        $value = $this->get($key);

        if ($value === null) {
            return null;
        }

        return ini_parse_quantity($value);
    }

    public function getFloat(string $key, ?int $default = null): ?float
    {
        $value = $this->get($key, $default);

        if ($value === $default) {
            return $default;
        }

        return (float) $value;
    }

    public function getBool(string $key, ?bool $default = null): ?bool
    {
        $value = $this->get($key, $default);

        if ($value === $default) {
            return $default;
        }

        return (bool) $value;
    }

    /**
     * @return mixed[]
     */
    public function all(?string $key = null): array
    {
        if (null === $key) {
            return $this->parameters;
        }

        if (!\is_array($value = $this->parameters[$key] ?? [])) {
            throw new BadRequestException(\sprintf('Unexpected value for parameter "%s": expecting "array", got "%s".', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param mixed[] $parameters
     */
    public function withParameters(array $parameters): static
    {
        $new = clone $this;
        $new->parameters = $parameters;

        return $new;
    }
}
