<?php

declare(strict_types=1);

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Fake field, just to make usage simpler.
 */
class CreatedAtField
{
    public static function new(string $propertyName = 'createdAt', TranslatableInterface|string|false|null $label = 'Created at'): FieldInterface
    {
        return DateTimeField::new($propertyName)
            ->setLabel($label)
            ->setDisabled()
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
        ;
    }
}
