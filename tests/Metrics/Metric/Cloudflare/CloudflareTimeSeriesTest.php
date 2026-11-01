<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Metric\Cloudflare;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Tests\Metrics\Renderer\CloudflareCardTestCase;

final class CloudflareTimeSeriesTest extends CloudflareCardTestCase
{
    /**
     * @return iterable<string, array{Metric, string[], mixed}>
     */
    public static function series(): iterable
    {
        yield 'bandwidth' => [Metric::CloudflareBandwidth, ['bytes', 'cached_bytes'], 25778];
        yield 'origin requests' => [Metric::CloudflareOriginRequests, ['origin_requests'], 13];
        yield 'error ratio' => [Metric::CloudflareErrorRatio, ['status_4xx_percent', 'status_5xx_percent'], null];
    }

    /**
     * @param string[] $fields
     */
    #[DataProvider('series')]
    public function testTheSeriesReadTheCloudflareMeasurement(Metric $metric, array $fields, mixed $currentValue): void
    {
        $dto = $this->dto($metric, Renderer::Line, self::fixture());

        $this->assertInstanceOf(TimeSerieDto::class, $dto);
        $this->assertTrue($dto->valueAvailable);
        $this->assertSame('cloudflare', $dto->measurement);
        $this->assertSame($fields, array_keys($dto->fields));
        $this->assertSame(60, $dto->minimumWindow);

        if ($currentValue !== null) {
            $this->assertSame($currentValue, $dto->currentValue[0]);
        }
    }

    /**
     * @param string[] $fields
     */
    #[DataProvider('series')]
    public function testAMissingTrafficBlockKeepsTheHistoryAndCarriesItsReason(Metric $metric, array $fields): void
    {
        $payload = self::fixture();
        unset($payload['traffic']);
        $payload['unavailable'] = ['traffic' => 'rate_limited'];

        $dto = $this->dto($metric, Renderer::Line, $payload);

        $this->assertInstanceOf(TimeSerieDto::class, $dto);
        $this->assertTrue($dto->valueAvailable);
        $this->assertSame($fields, array_keys($dto->fields));
        $this->assertSame('Cloudflare rate limit reached, retrying.', $dto->emptyDataReason);
    }
}
