<?php

declare(strict_types=1);

namespace App\Tests\Bridge\InfluxDb;

use App\Bridge\InfluxDb\InfluxDb;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use GuzzleHttp\Psr7\Response;
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

    private function createInfluxDb(string $orgId, ClientInterface $httpClient): InfluxDb
    {
        return new InfluxDb(
            url: 'http://influxdb:8086',
            token: 'dev-token',
            orgId: $orgId,
            orgName: 'jmonitor',
            logger: new NullLogger(),
            httpClient: $httpClient,
            planResolver: new PlanResolver(Edition::SELF_HOSTED),
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
