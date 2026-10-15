<?php

declare(strict_types=1);

namespace App\Tests\Bridge\InfluxDb;

use App\Bridge\InfluxDb\InfluxDb;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use GuzzleHttp\Psr7\Response;
use InfluxDB2\ApiException;
use InfluxDB2\Point;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

class InfluxDbTest extends TestCase
{
    public function testGetOrgIdReturnsEnvValueWithoutHttpCall(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{}'));
        $influxDb = $this->createInfluxDb(orgId: 'abc123', httpClient: $http);

        $this->assertSame('abc123', $influxDb->getOrgId());
        $this->assertCount(0, $http->requests);
    }

    public function testGetOrgIdResolvesByNameWhenEnvIsEmpty(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{"orgs":[{"id":"dev-org-id","name":"jmonitor"}]}'));
        $influxDb = $this->createInfluxDb(orgId: '', httpClient: $http);

        $this->assertSame('dev-org-id', $influxDb->getOrgId());
        $this->assertCount(1, $http->requests);
        $request = $http->requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://influxdb:8086/api/v2/orgs?org=jmonitor', (string) $request->getUri());
        $this->assertSame('Token dev-token', $request->getHeaderLine('Authorization'));
    }

    public function testGetOrgIdIsMemoized(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{"orgs":[{"id":"dev-org-id","name":"jmonitor"}]}'));
        $influxDb = $this->createInfluxDb(orgId: '', httpClient: $http);

        $influxDb->getOrgId();
        $influxDb->getOrgId();

        $this->assertCount(1, $http->requests);
    }

    public function testGetOrgIdThrowsWhenOrgIsNotFound(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{"orgs":[]}'));
        $influxDb = $this->createInfluxDb(orgId: '', httpClient: $http);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('jmonitor');
        $influxDb->getOrgId();
    }

    public function testWritePointsGoesThroughTheWriteHttpClient(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{}'));
        $writeHttp = new RecordingHttpClient(new Response(204));
        $influxDb = $this->createInfluxDb(orgId: 'abc123', httpClient: $http, writeHttpClient: $writeHttp);

        $influxDb->writePoints([Point::measurement('cpu')->addField('load', 1)->time(1700000000)], 'bucket-id');

        $this->assertCount(0, $http->requests);
        $this->assertCount(1, $writeHttp->requests);
        $request = $writeHttp->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringStartsWith('http://influxdb:8086/api/v2/write?', (string) $request->getUri());
        $this->assertStringContainsString('bucket=bucket-id', (string) $request->getUri());
    }

    public function testWritePointsDoesNotRetryAFailedWrite(): void
    {
        $writeHttp = new RecordingHttpClient(new Response(503));
        $influxDb = $this->createInfluxDb(orgId: 'abc123', httpClient: $writeHttp);

        try {
            $influxDb->writePoints([Point::measurement('cpu')->addField('load', 1)->time(1700000000)], 'bucket-id');
            $this->fail('A failed write must throw.');
        } catch (ApiException) {
        }

        $this->assertCount(1, $writeHttp->requests);
    }

    private function createInfluxDb(string $orgId, ClientInterface $httpClient, ?ClientInterface $writeHttpClient = null): InfluxDb
    {
        return new InfluxDb(
            url: 'http://influxdb:8086',
            token: 'dev-token',
            orgId: $orgId,
            orgName: 'jmonitor',
            logger: new NullLogger(),
            httpClient: $httpClient,
            planResolver: new PlanResolver(Edition::SELF_HOSTED),
            writeHttpClient: $writeHttpClient ?? $httpClient,
        );
    }
}

class RecordingHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    public function __construct(private readonly ResponseInterface $response) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return $this->response;
    }
}
