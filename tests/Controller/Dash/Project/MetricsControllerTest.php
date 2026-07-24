<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project;

use App\Controller\Dash\Project\MetricsController;
use App\Entity\Embed;
use App\Entity\Project;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Repository\EmbedRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class MetricsControllerTest extends TestCase
{
    public function testCreateEmbedIsDeniedOnFreePlan(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, null, false, null);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $this->expectException(AccessDeniedException::class);

        new MetricsController()->createEmbed($project, $dto, new Request(), $em, new PlanResolver(Edition::CLOUD));
    }

    public function testUpdateEmbedIsDeniedOnFreePlan(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, null, false, null);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        new MetricsController()->updateEmbed($project, 'token', $dto, new Request(), $this->createMock(EmbedRepository::class), $em, new PlanResolver(Edition::CLOUD));
    }

    public function testUpdateEmbedWithAnInvalidCsrfTokenIsDenied(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, null, false, null);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        // The SELF_HOSTED edition resolves to an embedable plan, so the CSRF check is reached.
        $this->makeController(csrfValid: false)->updateEmbed($project, 'token', $dto, new Request(), $this->createMock(EmbedRepository::class), $em, new PlanResolver(Edition::SELF_HOSTED));
    }

    public function testUpdatingAnUnknownOrForeignTokenIs404(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, null, false, null);
        $repository = $this->createMock(EmbedRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['token' => 'unknown', 'project' => $project])->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeController(csrfValid: true)->updateEmbed($project, 'unknown', $dto, new Request(), $repository, $this->createMock(EntityManagerInterface::class), new PlanResolver(Edition::SELF_HOSTED));
    }

    public function testUpdateEmbedReplacesTheDtoAndKeepsTheToken(): void
    {
        $project = new Project();
        $embed = new Embed()->setProject($project)->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, null, false, null));
        $token = $embed->getToken();

        $newDto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, null, true, null);
        $repository = $this->createMock(EmbedRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['token' => $token, 'project' => $project])->willReturn($embed);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->once())->method('generate')->with('project.metrics.embed', $this->callback(fn(array $params): bool => ($params['edit'] ?? null) === $token && ($params['updated'] ?? null) === 1))->willReturn('/redirected');

        $container = new Container();
        $container->set('security.csrf.token_manager', $csrfTokenManager);
        $container->set('router', $router);

        $controller = new MetricsController();
        $controller->setContainer($container);

        $response = $controller->updateEmbed($project, $token, $newDto, new Request(), $repository, $em, new PlanResolver(Edition::SELF_HOSTED));

        $this->assertEquals($newDto, $embed->getDto());
        $this->assertSame($token, $embed->getToken());
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testUpdateEmbedRejectsAMetricChange(): void
    {
        $project = new Project();
        $embed = new Embed()->setProject($project)->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, null, false, null));
        $token = $embed->getToken();

        $newDto = new EmbedDto(Metric::SystemRamUsage, Renderer::Gauge, null, false, null);
        $repository = $this->createMock(EmbedRepository::class);
        $repository->method('findOneBy')->willReturn($embed);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(BadRequestHttpException::class);

        $this->makeController(csrfValid: true)->updateEmbed($project, $token, $newDto, new Request(), $repository, $em, new PlanResolver(Edition::SELF_HOSTED));
    }

    private function makeController(bool $csrfValid): MetricsController
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn($csrfValid);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/redirected');

        $container = new Container();
        $container->set('security.csrf.token_manager', $csrfTokenManager);
        $container->set('router', $router);

        $controller = new MetricsController();
        $controller->setContainer($container);

        return $controller;
    }
}
