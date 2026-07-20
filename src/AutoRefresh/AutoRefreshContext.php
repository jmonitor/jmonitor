<?php

declare(strict_types=1);

namespace App\AutoRefresh;

use App\AutoRefresh\Dto\AutoRefreshDto;
use App\Form\AutoRefreshType;
use App\Security\Voter\Right\Right;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ResetInterface;

class AutoRefreshContext implements ResetInterface
{
    private ?AutoRefreshDto $autoRefreshDto = null;
    private ?FormInterface $form = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Lazy]
        private readonly FormFactoryInterface $formFactory,
        private readonly Security $security,
    ) {}

    public function getForm(): FormInterface
    {
        return $this->form ??= $this->formFactory->create(AutoRefreshType::class, $this->getDto());
    }

    public function getDto(): AutoRefreshDto
    {
        return $this->autoRefreshDto
            ??= new AutoRefreshDto($this->security->isGranted(Right::AUTOREFRESH->value)
            ? $this->getSession()->get('autoRefresh', false) : false);
    }

    public function save(): void
    {
        $this->getSession()->set('autoRefresh', $this->getDto()->autoRefresh);
    }

    public function isAutoRefresh(): bool
    {
        return $this->getDto()->autoRefresh;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function reset(): void
    {
        $this->autoRefreshDto = null;
        $this->form = null;
    }
}
