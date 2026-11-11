<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use Symfony\Component\Intl\Countries;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareByCountry->value)]
class CloudflareByCountry extends AbstractCloudflareBreakdown
{
    public function getMetric(): Metric
    {
        return Metric::CloudflareByCountry;
    }

    protected function counts(WindowBag $window): array
    {
        return $window->byCountry;
    }

    protected function label(string $raw): string
    {
        if (!preg_match('/^[A-Z]{2}$/', $raw) || !Countries::exists($raw)) {
            return $raw;
        }

        return self::flag($raw) . ' ' . Countries::getName($raw, 'en');
    }

    private static function flag(string $code): string
    {
        return implode('', array_map(fn(string $letter): string => mb_chr(0x1F1E6 + ord($letter) - ord('A')), str_split($code)));
    }
}
