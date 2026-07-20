<?php

declare(strict_types=1);

namespace App\Range;

use App\Chart\TimeRange;
use App\Range\Dto\RangeDto;
use App\Form\RangeType;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ResetInterface;

class RangeContext implements ResetInterface
{
    private ?RangeDto $rangeDto = null;
    private ?FormInterface $form = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Lazy]
        private readonly FormFactoryInterface $formFactory,
    ) {}

    public function getForm(): FormInterface
    {
        return $this->form ??= $this->formFactory->create(RangeType::class, $this->getRangeDto());
    }

    public function getRangeDto(): RangeDto
    {
        if ($this->rangeDto) {
            return $this->rangeDto;
        }

        $range = $this->getSession()->get('range');

        return $this->rangeDto = new RangeDto($range ? TimeRange::tryFrom($range) : null);
    }

    public function save(): void
    {
        $this->getSession()->set('range', $this->getRangeDto()->range->value);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function reset(): void
    {
        $this->rangeDto = null;
        $this->form = null;
    }
}
