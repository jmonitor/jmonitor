<?php

// tests/Controller/Dash/Project/Settings/SettingsEmbedControllerTest.php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project\Settings;

use App\Controller\Dash\Project\Settings\SettingsEmbedController;
use App\Entity\Embed;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
}
