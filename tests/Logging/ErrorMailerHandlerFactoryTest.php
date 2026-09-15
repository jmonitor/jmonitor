<?php

declare(strict_types=1);

namespace App\Tests\Logging;

use App\Logging\ErrorMailerHandlerFactory;
use App\Logging\FailSafeMailerHandler;
use Monolog\Handler\NullHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use PHPUnit\Framework\TestCase;

final class ErrorMailerHandlerFactoryTest extends TestCase
{
    public function testReturnsMailerHandlerWhenRecipientIsSet(): void
    {
        $factory = new ErrorMailerHandlerFactory(
            $this->createMock(MailerInterface::class),
            'from@example.com',
            'errors@example.com',
        );

        self::assertInstanceOf(FailSafeMailerHandler::class, $factory->create());
    }

    public function testReturnsNullHandlerWhenRecipientIsEmpty(): void
    {
        $factory = new ErrorMailerHandlerFactory(
            $this->createMock(MailerInterface::class),
            'from@example.com',
            '',
        );

        self::assertInstanceOf(NullHandler::class, $factory->create());
    }

    public function testReturnsNullHandlerWhenRecipientIsNull(): void
    {
        $factory = new ErrorMailerHandlerFactory(
            $this->createMock(MailerInterface::class),
            'from@example.com',
            null,
        );

        self::assertInstanceOf(NullHandler::class, $factory->create());
    }

    public function testSubjectIsStaticEvenForLongMessages(): void
    {
        $email = $this->handleRecord(str_repeat('Failed to authenticate on SMTP server. ', 100));

        self::assertSame('[JMonitor] Application error', $email->getSubject());
    }

    public function testBodyContainsFullMessage(): void
    {
        $message = str_repeat('Failed to authenticate on SMTP server. ', 100);

        $email = $this->handleRecord($message);

        self::assertStringContainsString(rtrim($message), (string) $email->getHtmlBody());
    }

    public function testTransportFailureDoesNotPropagate(): void
    {
        $mailer = new class implements MailerInterface {
            public int $attempts = 0;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                ++$this->attempts;

                throw new TransportException('451 4.4.2 Timeout waiting for data from client.');
            }
        };

        $handler = new ErrorMailerHandlerFactory($mailer, 'from@example.com', 'errors@example.com')->create();
        $record = new LogRecord(new \DateTimeImmutable(), 'app', Level::Error, 'Invalid metrics');

        $errorLog = ini_set('error_log', '/dev/null');

        try {
            $handler->handle($record);
            $handler->handleBatch([$record]);
        } finally {
            ini_set('error_log', (string) $errorLog);
        }

        self::assertSame(2, $mailer->attempts);
    }

    private function handleRecord(string $message): Email
    {
        $mailer = new class implements MailerInterface {
            public ?RawMessage $sent = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent = $message;
            }
        };

        $factory = new ErrorMailerHandlerFactory($mailer, 'from@example.com', 'errors@example.com');
        $factory->create()->handle(new LogRecord(new \DateTimeImmutable(), 'app', Level::Critical, $message));

        self::assertInstanceOf(Email::class, $mailer->sent);

        return $mailer->sent;
    }
}
