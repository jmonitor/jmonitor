<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project\Settings;

use App\Controller\Dash\Project\Settings\SettingsEmbedController;
use App\Entity\Embed;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SettingsEmbedControllerTest extends TestCase
{
    public function testRevokingAnotherProjectsEmbedIs404(): void
    {
        $embed = new Embed()->setProject(new Project());

        $this->expectException(NotFoundHttpException::class);

        new SettingsEmbedController()->revoke(
            new Project(),
            $embed,
            $this->createMock(EntityManagerInterface::class),
            new Request(),
        );
    }

    public function testDeletingAnotherProjectsEmbedIs404(): void
    {
        $embed = new Embed()->setProject(new Project());

        $this->expectException(NotFoundHttpException::class);

        new SettingsEmbedController()->delete(
            new Project(),
            $embed,
            $this->createMock(EntityManagerInterface::class),
            new Request(),
        );
    }

    public function testRevokingWithAnInvalidCsrfTokenIsDenied(): void
    {
        $project = new Project();
        $embed = new Embed()->setProject($project);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        $this->makeController()->revoke($project, $embed, $em, new Request());
    }

    public function testDeletingWithAnInvalidCsrfTokenIsDenied(): void
    {
        $project = new Project();
        $embed = new Embed()->setProject($project);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        $this->makeController()->delete($project, $embed, $em, new Request());
    }

    private function makeController(): SettingsEmbedController
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);

        $container = new Container();
        $container->set('security.csrf.token_manager', $csrfTokenManager);

        $controller = new SettingsEmbedController();
        $controller->setContainer($container);

        return $controller;
    }
}
