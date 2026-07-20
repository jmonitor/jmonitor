<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\AutoRefresh\Dto\AutoRefreshDto;
use App\Entity\Enums\Component;
use App\Form\AutoRefreshType;
use App\Metrics\Dto\EmbedDto;
use App\Security\Voter\Right\Right;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(template: 'components/AutoRefreshForm.html.twig')]
class EmbedAutoRefreshForm
{
    public EmbedDto $embedDto;
    private ?AutoRefreshDto $autoRefreshDto = null;
    private readonly FormFactoryInterface $formFactory;
    private readonly Security $security;

    public function __construct(FormFactoryInterface $formFactory, Security $security)
    {
        $this->formFactory = $formFactory;
        $this->security = $security;
    }

    #[ExposeInTemplate]
    public function getForm(): FormView
    {
        $form = $this->formFactory->createNamed(AutoRefreshType::EMBED_NAME, AutoRefreshType::class, $this->getRefreshDto());

        return $form->createView();
    }

    #[ExposeInTemplate]
    public function getRefreshDto(): AutoRefreshDto
    {
        return $this->autoRefreshDto ??= new AutoRefreshDto(
            $this->security->isGranted(Right::AUTOREFRESH->value) ? $this->embedDto->autoRefresh : false,
        );
    }

    #[ExposeInTemplate]
    public function getComponent(): Component
    {
        return $this->embedDto->metric->component();
    }
}
