<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project;

use App\Controller\Dash\Project\MetricsController;
use App\Entity\Project;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
}
