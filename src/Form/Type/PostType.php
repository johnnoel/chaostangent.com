<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Model\PostModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PostModel>
 */
class PostType extends AbstractType
{
    /** @inheritdoc */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'required' => true,
            'trim' => true,
        ])->add('subtitle', TextType::class, [
            'required' => false,
            'trim' => true,
        ])->add('alias', TextType::class, [
            'required' => true,
            'trim' => true,
        ])->add('summary', TextareaType::class, [
            'required' => false,
        ])->add('date', DateTimeType::class, [
            'required' => true,
            'input' => 'datetime_immutable',
            'invalid_message' => 'Please enter a valid date/time for date',
        ])->add('created', DateTimeType::class, [
            'required' => true,
            'input' => 'datetime_immutable',
            'invalid_message' => 'Please enter a valid date/time for created',
        ])->add('updated', DateTimeType::class, [
            'required' => true,
            'input' => 'datetime_immutable',
            'invalid_message' => 'Please enter a valid date/time for updated',
        ])->add('published', CheckboxType::class, [
            'required' => false,
        ])->add('extra', ExtraType::class)->add('image', ImageType::class, [
            'required' => false,
        ])->add('categories', CategoriesType::class, [
            'required' => false,
            'invalid_message' => 'Please enter valid categories',
        ])->add('tags', TagsType::class, [
            'required' => false,
            'invalid_message' => 'Please enter valid tags',
        ])->add('content', TextareaType::class, [
            'required' => true,
            'trim' => true,
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostModel::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }
}
