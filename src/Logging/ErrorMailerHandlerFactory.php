<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Level;
use Symfony\Bridge\Monolog\Handler\MailerHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Builds the monolog handler for error emails: NullHandler when ERROR_MAIL_TO
 * is empty, a MailerHandler otherwise.
 */
final readonly class ErrorMailerHandlerFactory
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%app.mailer_sender_email%')]
        private string $fromEmail,
        #[Autowire('%env(default::ERROR_MAIL_TO)%')]
        private ?string $toEmail = null,
    ) {}

    public function create(): HandlerInterface
    {
        if (null === $this->toEmail || '' === $this->toEmail) {
            return new NullHandler();
        }

        // Static subject, no %message%: an unbounded subject can exceed
        // downstream limits (e.g. the messenger monitoring description column).
        // The full error is in the HTML body.
        $prototype = (new Email())
            ->from($this->fromEmail)
            ->to($this->toEmail)
            ->subject('[JMonitor] Application error');

        // Synchronous send ("sync" transport), bypassing Messenger/Redis: the crash
        // email must go out even when Redis is down — otherwise SendEmailMessage:async
        // fails to enqueue and the alert is lost at the worst moment.
        $prototype->getHeaders()->addTextHeader('X-Bus-Transport', 'sync');

        $handler = new MailerHandler($this->mailer, $prototype, Level::Debug);
        $handler->setFormatter(new HtmlFormatter());

        return $handler;
    }
}
