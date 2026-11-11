<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Metric;
use App\Metrics\MetricDtoProvider;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\AbstractDto;
use Symfony\Component\DomCrawler\Crawler;

abstract class CloudflareCardTestCase extends PayloadCardTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../Consumer/Cloudflare/fixtures/payload_v1.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function render(Metric $metric, array $payload, bool $skipped = false, ?Renderer $renderer = null): string
    {
        $this->push(CloudflareConsumer::class, $payload, skipped: $skipped);

        return $this->renderCard($metric, $renderer);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function dto(Metric $metric, Renderer $renderer, array $payload): ?AbstractDto
    {
        $this->push(CloudflareConsumer::class, $payload);

        return self::getContainer()->get(MetricDtoProvider::class)->getDto($metric, $renderer);
    }

    /**
     * @return array<string, string> label => value of every InfoLine in the html
     */
    protected static function lines(string $html): array
    {
        $lines = [];
        new Crawler($html)->filter('.dldt')->each(function (Crawler $line) use (&$lines): void {
            $lines[trim($line->filter('.dl')->text())] = trim(preg_replace('/\s+/', ' ', $line->filter('.dt')->text()) ?? '');
        });

        return $lines;
    }
}
