<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Tag;
use App\Form\DataTransformer\TagsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<Tag>>
 */
class TagsType extends AbstractType
{
    public function __construct(private readonly TagsTransformer $transformer)
    {
    }

    /** @inheritdoc */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->transformer);
    }

    /**
     * @return class-string
     */
    #[\Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => TextType::class,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
    }
}
