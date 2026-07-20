<?php

declare(strict_types=1);

namespace App\EventListener\Range;

use App\Range\RangeContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
readonly class RangeFormListener
{
    public function __construct(
        private RangeContext $rangeContext,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->request->has('range')) {
            return;
        }

        $form = $this->rangeContext->getForm();

        $form->handleRequest($event->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->rangeContext->save();

            $event->setResponse(new RedirectResponse($event->getRequest()->getRequestUri()));
        }
    }
}
