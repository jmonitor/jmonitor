<?php

declare(strict_types=1);

namespace App\Alerting;

use App\Alerting\Dto\SpottedAlert;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

readonly class AlertNotifier
{
    public function __construct(
        private Environment $twig,
        private MailerInterface $mailer,
        #[Autowire('%app.alert_mail_from%')]
        private string $alertFromEmail,
    ) {}

    public function notify(SpottedAlert $spottedAlert): void
    {
        $project = $spottedAlert->getAlert()->getProject();

        foreach ($this->getNotifiedUsers($project) as $user) {
            $this->doNotify($spottedAlert, $user);
        }
    }

    private function getNotifiedUsers(Project $project): iterable
    {
        $projectUsers = array_filter($project->getProjectUsers(), fn(ProjectUser $projectUser): bool => $projectUser->isAlertNotificationsEnabled());

        foreach ($projectUsers as $projectUser) {
            yield $projectUser->getUser();
        }
    }

    private function doNotify(SpottedAlert $spottedAlert, User $user): void
    {
        // TODO go through the Notifier component instead of building the email manually
        $email = new Email();
        $email->from(new Address($this->alertFromEmail, 'Jmonitor'));
        $email->to($user->getEmail());
        $alertMetric = $spottedAlert->getAlert()->getAlertMetric();
        $subject = sprintf(
            '[ALERT][%s] %s - %s alert triggered',
            $spottedAlert->getAlert()->getProject()->getName(),
            $alertMetric->component()->label(),
            $alertMetric->label(),
        );
        $email->subject(mb_substr($subject, 0, 125));
        $email->html($this->twig->render('email/alerting/alert_spotted.html.twig', [
            'spottedAlert' => $spottedAlert,
        ]));

        $this->mailer->send($email);
    }
}
