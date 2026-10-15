<?php

declare(strict_types=1);

namespace App\Log\Monolog;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

#[AsMonologProcessor(handler: 'main')]
readonly class AppDatasProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        #[Autowire(service: 'security.untracked_token_storage')]
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $token = $this->tokenStorage->getToken();

        if ($user = $token?->getUser()) {
            $record->extra['logged_user'] = $user->getUserIdentifier();
        }

        if ($token instanceof SwitchUserToken) {
            $impersonatorUser = $token->getOriginalToken()->getUser();
            $record->extra['impersonator_user'] = $impersonatorUser->getUserIdentifier();
        }

        if ($requestInfos = $this->getRequestInfos()) {
            $record->extra['request'] = $requestInfos;
        }

        return $record;
    }

    /**
     * @return array{current_uri?: string, main_uri?: string, method?: string, ip?: string, referer?: string}
     */
    private function getRequestInfos(): array
    {
        $output = [];

        if (!$this->requestStack->getCurrentRequest()) {
            return $output;
        }

        $output['current_uri'] = $this->requestStack->getCurrentRequest()->getUri();

        if ($this->requestStack->getMainRequest() !== $this->requestStack->getCurrentRequest()) {
            $output['main_uri'] = $this->requestStack->getMainRequest()?->getUri();
        }

        $output['method'] = $this->requestStack->getCurrentRequest()->getMethod();
        $output['ip'] = $this->requestStack->getCurrentRequest()->getClientIp();
        $output['referer'] = $this->requestStack->getCurrentRequest()->headers->get('referer');

        if ($route = $this->requestStack->getCurrentRequest()->attributes->get('_route')) {
            $output['route'] = $route;
        }

        return array_filter($output);
    }
}
