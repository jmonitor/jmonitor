<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project;

use App\Controller\Dash\Project\MetricsController;
use App\Entity\Embed;
use App\Entity\Project;
use App\Form\Embed\CardEmbedOptionsType;
use App\Form\Embed\EmbedType;
use App\Form\Embed\GaugeEmbedOptionsType;
use App\Form\Embed\TimeSeriesEmbedOptionsType;
use App\Metrics\Dto\Embed\CardEmbedOptions;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\MetricLocator;
use App\Metrics\Renderer;
use App\Metrics\Renderer\ChartDefaultsResolver;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Repository\EmbedRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validation;

class MetricsControllerTest extends TestCase
{
    public function testCreateEmbedIsDeniedOnFreePlan(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $this->expectException(AccessDeniedException::class);

        new MetricsController()->createEmbed($project, $dto, new Request(), $em, new PlanResolver(Edition::CLOUD));
    }

    public function testUpdateEmbedIsDeniedOnFreePlan(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        new MetricsController()->updateEmbed($project, 'token', $dto, new Request(), $this->createMock(EmbedRepository::class), $em, new PlanResolver(Edition::CLOUD));
    }

    public function testUpdateEmbedWithAnInvalidCsrfTokenIsDenied(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        // The SELF_HOSTED edition resolves to an embedable plan, so the CSRF check is reached.
        $this->makeController(csrfValid: false)->updateEmbed($project, 'token', $dto, new Request(), $this->createMock(EmbedRepository::class), $em, new PlanResolver(Edition::SELF_HOSTED));
    }

    public function testUpdatingAnUnknownOrForeignTokenIs404(): void
    {
        $project = new Project();
        $dto = new EmbedDto(Metric::SystemCpuUsage, null, false);
        $repository = $this->createMock(EmbedRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['token' => 'unknown', 'project' => $project])->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeController(csrfValid: true)->updateEmbed($project, 'unknown', $dto, new Request(), $repository, $this->createMock(EntityManagerInterface::class), new PlanResolver(Edition::SELF_HOSTED));
    }

    public function testUpdateEmbedReplacesTheDtoAndKeepsTheToken(): void
    {
        $project = new Project();
        $embed = new Embed()->setProject($project)->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, false));
        $token = $embed->getToken();

        // chart must already hold the renderer's default options: getDto() always fills it in via fromArray().
        $newDto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, true, chart: new TimeSeriesEmbedOptions());
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
        $embed = new Embed()->setProject($project)->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, false));
        $token = $embed->getToken();

        $newDto = new EmbedDto(Metric::SystemRamUsage, Renderer::Gauge, false);
        $repository = $this->createMock(EmbedRepository::class);
        $repository->method('findOneBy')->willReturn($embed);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(BadRequestHttpException::class);

        $this->makeController(csrfValid: true)->updateEmbed($project, $token, $newDto, new Request(), $repository, $em, new PlanResolver(Edition::SELF_HOSTED));
    }

    // Regression: a submission with no "renderer" value is still valid and synchronised
    // (ChoiceType has no server-side "required" constraint) — the resolved renderer must
    // still get chart options that match it, so GaugeEmbedOptions::applyTo() keeps forcing
    // displayHelp(false).
    public function testEmbedFallsBackToTheMetricsDefaultRendererWhenNoneIsSubmitted(): void
    {
        // SystemRamUsage offers Gauge + Line, and defaults to Gauge.
        $project = new Project();
        $embedDto = new EmbedDto(Metric::SystemRamUsage, Renderer::Gauge, false, new CardEmbedOptions(), new GaugeEmbedOptions());

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/redirected');

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn(false);

        $container = new Container();
        $container->set('form.factory', $this->embedFormFactory());
        $container->set('router', $router);
        $container->set('security.authorization_checker', $authorizationChecker);

        $capturedEmbed = null;
        $controller = $this->getMockBuilder(MetricsController::class)
            ->onlyMethods(['render'])
            ->getMock();
        $controller->method('render')->willReturnCallback(function (string $view, array $parameters) use (&$capturedEmbed): Response {
            $capturedEmbed = $parameters['embed'];

            return new Response();
        });
        $controller->setContainer($container);

        // No "renderer" key at all — the same shape a hand-crafted POST omitting the style
        // choice would submit.
        $request = Request::create('/', 'POST', ['embed' => ['autoRefresh' => '', 'card' => []]]);

        $response = $controller->embed($project, $request, $this->createMock(EmbedRepository::class), new PlanResolver(Edition::SELF_HOSTED), $embedDto);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(EmbedDto::class, $capturedEmbed);
        $this->assertSame(Renderer::Gauge, $capturedEmbed->renderer);
        $this->assertInstanceOf(GaugeEmbedOptions::class, $capturedEmbed->chart);
    }

    /**
     * A hand-assembled form factory carrying only the types EmbedType needs, rather than the
     * real DI-backed one: the real "form.factory" service pulls in the whole form type
     * registry, and an unrelated service in it fails to construct in this test env
     * (Symfony\Component\Mercure\Hub built with a null URL) — a pre-existing environment gap,
     * not something this test is about.
     */
    private function embedFormFactory(): FormFactoryInterface
    {
        $metricLocatorContainer = new class implements ContainerInterface {
            public function get(string $id): never
            {
                throw new \LogicException('No metric service needed: this submission never reaches the time series branch.');
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new HttpFoundationExtension())
            ->addType(new EmbedType(new ChartDefaultsResolver(new MetricLocator($metricLocatorContainer))))
            ->addType(new CardEmbedOptionsType())
            ->addType(new GaugeEmbedOptionsType())
            ->addType(new TimeSeriesEmbedOptionsType())
            ->getFormFactory();
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
