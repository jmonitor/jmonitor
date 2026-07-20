<?php

declare(strict_types=1);

namespace App\Tests\Logging;

use App\Logging\ErrorMailerHandlerFactory;
use Monolog\Handler\NullHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bridge\Monolog\Handler\MailerHandler;
use Symfony\Component\Mailer\Envelope;
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

        self::assertInstanceOf(MailerHandler::class, $factory->create());
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
