<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Metric;

use App\Bridge\Mercure\MercureTopicProvider;
use App\Entity\Project;
use App\Event\PostConsumeEvent;
use App\EventListener\Metric\AutoRefreshListener;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Project\ProjectContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AutoRefreshListenerTest extends TestCase
{
    public function testProjectTopicIsPrivateAndComponentTopicsArePublic(): void
    {
        /** @var Update[] $updates */
        $updates = [];
        $hub = new MockHub('http://app/.well-known/mercure', new StaticTokenProvider('jwt'), function (Update $update) use (&$updates): string {
            $updates[] = $update;

            return 'id';
        });

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $topicProvider = new MercureTopicProvider(
            'jmonitor.io',
            $this->createMock(ProjectContext::class),
            new Authorization(new HubRegistry($hub)),
            new RequestStack(),
            $this->createMock(LoggerInterface::class),
        );

        $project = new Project();
        $receivedAt = new \DateTimeImmutable();
        $event = new PostConsumeEvent($project, [MetricBagDto::create($project, Consumer::SYSTEM, 1, [], $receivedAt, false)], $receivedAt, '2.4.0');

        new AutoRefreshListener($hub, $topicProvider, $security, $this->createMock(LoggerInterface::class))($event);

        $this->assertCount(2, $updates);

        // The hub accepts anonymous subscribers: only public embeds' sub-topics may reach them.
        $this->assertSame([$topicProvider->getConsumedMetricUrl($project)], $updates[0]->getTopics());
        $this->assertTrue($updates[0]->isPrivate());

        $this->assertSame([$topicProvider->getConsumedMetricComponentUrl($project, 'system')], $updates[1]->getTopics());
        $this->assertFalse($updates[1]->isPrivate());
    }
}
