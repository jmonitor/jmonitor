<?php

declare(strict_types=1);

namespace App\Tests\Log\Monolog;

use App\Log\Monolog\AppDatasProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AppDatasProcessorTest extends KernelTestCase
{
    public function testTheLoggedUserIsAddedToTheRecord(): void
    {
        $extra = $this->processWithToken(new UsernamePasswordToken(new InMemoryUser('user@example.com', null), 'main'));

        self::assertSame('user@example.com', $extra['logged_user']);
        self::assertArrayNotHasKey('impersonator_user', $extra);
    }

    public function testTheImpersonatorIsAddedToTheRecord(): void
    {
        $original = new UsernamePasswordToken(new InMemoryUser('admin@example.com', null), 'main');
        $extra = $this->processWithToken(new SwitchUserToken(new InMemoryUser('user@example.com', null), 'main', [], $original));

        self::assertSame('user@example.com', $extra['logged_user']);
        self::assertSame('admin@example.com', $extra['impersonator_user']);
    }

    public function testNoUserIsAddedWithoutToken(): void
    {
        self::assertArrayNotHasKey('logged_user', $this->processWithToken(null));
    }

    /**
     * @return array<string, mixed>
     */
    private function processWithToken(?TokenInterface $token): array
    {
        self::getContainer()->get('security.token_storage')->setToken($token);
        $processor = self::getContainer()->get(AppDatasProcessor::class);

        return $processor(new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'message'))->extra;
    }
}
