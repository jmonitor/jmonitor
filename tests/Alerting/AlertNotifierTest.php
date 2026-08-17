<?php

declare(strict_types=1);

namespace App\Tests\Alerting;

use App\Alerting\AlertMetric;
use App\Alerting\AlertNotifier;
use App\Alerting\Dto\SpottedAlert;
use App\Entity\Alert;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlertNotifierTest extends KernelTestCase
{
    public function testTheComponentIsCarriedBySubjectAndBody(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        $this->assertInstanceOf(Environment::class, $twig);

        $sent = [];
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(function (Email $email) use (&$sent): void {
            $sent[] = $email;
        });

        $notifier = new AlertNotifier($twig, $mailer, 'alert@jmonitor.io');
        $notifier->notify(new SpottedAlert($this->makeAlert(AlertMetric::MysqlVersion), 1));

        $this->assertCount(1, $sent);
        $this->assertSame('[ALERT][Jmonitor] MySQL - Version alert triggered', $sent[0]->getSubject());

        $body = $sent[0]->getHtmlBody();
        $this->assertIsString($body);
        $this->assertStringContainsString('Component : <b>MySQL</b>', $body);
    }

    private function makeAlert(AlertMetric $metric): Alert
    {
        $user = new User();
        $user->setEmail('user@jmonitor.io');

        $project = new Project();
        $project->setName('Jmonitor');
        $project->addProjectUser((new ProjectUser())->setUser($user)->setAlertNotificationsEnabled(true));

        $alert = new Alert();
        $alert->setAlertMetric($metric);
        $alert->setProject($project);

        return $alert;
    }
}
