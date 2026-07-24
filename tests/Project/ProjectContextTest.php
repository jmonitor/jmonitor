<?php

declare(strict_types=1);

namespace App\Tests\Project;

use App\Entity\Project;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ProjectContextTest extends TestCase
{
    public function testSetCurrentProjectShortCircuitsResolution(): void
    {
        $context = new ProjectContext(
            new RequestStack(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Security::class),
        );
        $project = new Project();

        $context->setCurrentProject($project);

        $this->assertSame($project, $context->getCurrentProject());
    }
}
