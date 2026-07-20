<?php

declare(strict_types=1);

namespace App\Notifier\Security;

use App\Notifier\Recipient\UserRecipient;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class PasswordLostNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(private readonly string $resetUrl)
    {
        parent::__construct();
    }

    #[\Override]
    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): ?EmailMessage
    {
        if (!$recipient instanceof UserRecipient) {
            throw new \LogicException(sprintf('Recipient of type "%s" is not supported.', $recipient::class));
        }

        $email = NotificationEmail::asPublicEmail()
            ->to($recipient->getEmail())
            ->htmlTemplate('email/security/password_lost.html.twig')
            ->subject('Password reset')
            ->context([

            ])
            ->action('Reset my password', $this->resetUrl)
        ;

        return new EmailMessage($email);
    }
}
