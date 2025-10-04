<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array{src: string, actions: array<string>}>
 */
class ImageType extends AbstractType
{
    /** @inheritdoc */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('src', TextType::class, [
            'required' => true,
        ])->add('actions', CollectionType::class, [
            'required' => false,
            'entry_type' => TextType::class,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
    }
}
