<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\MetricsReceivedMessage;
use PHPUnit\Framework\TestCase;

class MetricsReceivedMessageTest extends TestCase
{
    /**
     * A deployment leaves messages queued: they are unserialized by the code that
     * follows it, without ever going through the constructor.
     */
    public function testAMessageQueuedBeforeTheBundleVersionExistedStillUnserializes(): void
    {
        $message = unserialize($this->payloadWithoutBundleVersion());

        $this->assertInstanceOf(MetricsReceivedMessage::class, $message);
        $this->assertNull($message->getBundleVersion());
        $this->assertSame(42, $message->getProjectId());
        $this->assertSame(['mysql' => ['uptime' => 1000]], $message->getMetrics());
        $this->assertSame('2.1.0', $message->getJmonitorVersion());
        $this->assertSame('2026-08-14 13:00:00', $message->getReceivedAt()->format('Y-m-d H:i:s'));
    }

    public function testAQueuedBundleVersionSurvivesTheRoundTrip(): void
    {
        $message = unserialize(serialize(new MetricsReceivedMessage(42, [], '2.2.0', '2.1.0')));

        $this->assertSame('2.1.0', $message->getBundleVersion());
    }

    /**
     * What PHP's serializer wrote before the class gained $bundleVersion.
     */
    private function payloadWithoutBundleVersion(): string
    {
        $properties = [
            'receivedAt' => new \DateTimeImmutable('2026-08-14 13:00:00', new \DateTimeZone('UTC')),
            'projectId' => 42,
            'metrics' => ['mysql' => ['uptime' => 1000]],
            'jmonitorVersion' => '2.1.0',
        ];

        $class = MetricsReceivedMessage::class;
        $payload = sprintf('O:%d:"%s":%d:{', \strlen($class), $class, \count($properties));

        foreach ($properties as $name => $value) {
            $payload .= serialize(sprintf("\0%s\0%s", $class, $name)) . serialize($value);
        }

        return $payload . '}';
    }
}
