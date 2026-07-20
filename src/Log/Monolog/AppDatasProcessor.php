<?php

declare(strict_types=1);

namespace App\Log\Monolog;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

#[AsMonologProcessor(handler: 'main')]
readonly class AppDatasProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        if ($this->security->getUser()) {
            $record->extra['logged_user'] = $this->security->getUser()->getUserIdentifier();
        }

        $token = $this->security->getToken();
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
