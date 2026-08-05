<?php

declare(strict_types=1);

namespace App\Form\Embed;

use App\Metrics\Dto\Embed\CardEmbedOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The options object is readonly, so the form rebuilds it instead of writing into it.
 */
class CardEmbedOptionsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('showProjectName', CheckboxType::class, [
            'label' => 'Show project name',
            'required' => false,
        ]);

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CardEmbedOptions::class,
            'empty_data' => static fn(): CardEmbedOptions => new CardEmbedOptions(),
        ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);

        // "??" already short-circuits property access when $viewData is null, so no "?->" is needed here.
        $forms['showProjectName']->setData($viewData->showProjectName ?? false);
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $forms = iterator_to_array($forms);

        $viewData = new CardEmbedOptions((bool) $forms['showProjectName']->getData());
    }
}
