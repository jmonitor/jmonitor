<?php

declare(strict_types=1);

namespace App\Tests\Controller\ValueResolver;

use App\Controller\Attribute\MapEmbedDto;
use App\Controller\ValueResolver\EmbedDtoValueResolver;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EmbedDtoValueResolverTest extends TestCase
{
    public function testResolvesTheQueryParameter(): void
    {
        $request = new Request(['embed' => ['m' => 'system.cpu_usage']]);

        $resolved = new EmbedDtoValueResolver()->resolve($request, $this->argument());

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf(EmbedDto::class, $resolved[0]);
        $this->assertSame(Metric::SystemCpuUsage, $resolved[0]->metric);
    }

    public function testInvalidConfigurationIsABadRequest(): void
    {
        $request = new Request(['embed' => ['m' => 'does.not_exist']]);

        $this->expectException(BadRequestHttpException::class);

        new EmbedDtoValueResolver()->resolve($request, $this->argument());
    }

    public function testAMissingParameterResolvesToNullOnANullableArgument(): void
    {
        $resolved = new EmbedDtoValueResolver()->resolve(new Request(), $this->argument(nullable: true));

        $this->assertSame([null], $resolved);
    }

    public function testAMissingParameterIsABadRequestOnARequiredArgument(): void
    {
        $this->expectException(BadRequestHttpException::class);

        new EmbedDtoValueResolver()->resolve(new Request(), $this->argument());
    }

    public function testArgumentsWithoutTheAttributeAreIgnored(): void
    {
        $argument = new ArgumentMetadata('embedDto', EmbedDto::class, false, false, null);

        $this->assertSame([], new EmbedDtoValueResolver()->resolve(new Request(), $argument));
    }

    private function argument(bool $nullable = false): ArgumentMetadata
    {
        return new ArgumentMetadata('embedDto', EmbedDto::class, false, $nullable, null, $nullable, [new MapEmbedDto()]);
    }
}
