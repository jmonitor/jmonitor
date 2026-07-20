<?php

declare(strict_types=1);

namespace App\EventListener\AutoRefresh;

use App\AutoRefresh\AutoRefreshContext;
use App\Form\AutoRefreshType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
readonly class AutoRefreshFormListener
{
    public function __construct(
        private AutoRefreshContext $autoRefreshContext,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->request->has(AutoRefreshType::NAME)) {
            return;
        }

        $form = $this->autoRefreshContext->getForm();

        $form->handleRequest($event->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->autoRefreshContext->save();

            $event->setResponse(new RedirectResponse($event->getRequest()->getRequestUri()));
        }
    }
}
